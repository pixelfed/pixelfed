<?php

namespace App\Http\Controllers;

use App\Mail\CuratedRegisterAcceptUser;
use App\Mail\CuratedRegisterRejectUser;
use App\Mail\CuratedRegisterRequestDetailsFromUser;
use App\Models\CuratedRegister;
use App\Models\CuratedRegisterActivity;
use App\Models\CuratedRegisterTemplate;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AdminCuratedRegisterController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    public function index(Request $request)
    {
        $this->validate($request, [
            'filter' => 'sometimes|in:open,all,awaiting,approved,rejected,responses',
            'sort' => 'sometimes|in:asc,desc',
        ]);
        $filter = $request->input('filter', 'open');
        $sort = $request->input('sort', 'asc');
        $records = CuratedRegister::when($filter, function ($q, $filter) {
            if ($filter === 'open') {
                return $q->where('is_rejected', false)
                    ->where(function ($query) {
                        return $query->where('user_has_responded', true)->orWhere('is_awaiting_more_info', false);
                    })
                    ->whereNotNull('email_verified_at')
                    ->whereIsClosed(false);
            } elseif ($filter === 'all') {
                return $q;
            } elseif ($filter === 'responses') {
                return $q->whereIsClosed(false)
                    ->whereNotNull('email_verified_at')
                    ->where('user_has_responded', true)
                    ->where('is_awaiting_more_info', true);
            } elseif ($filter === 'awaiting') {
                return $q->whereIsClosed(false)
                    ->where('is_rejected', false)
                    ->where('is_approved', false)
                    ->where('user_has_responded', false)
                    ->where('is_awaiting_more_info', true);
            } elseif ($filter === 'approved') {
                return $q->whereIsClosed(true)->whereIsApproved(true);
            } elseif ($filter === 'rejected') {
                return $q->whereIsClosed(true)->whereIsRejected(true);
            }
        })
            ->when($sort, function ($query, $sort) {
                return $query->orderBy('id', $sort);
            })
            ->paginate(10)
            ->withQueryString();

        return view('admin.curated-register.index', compact('records', 'filter'));
    }

    public function show(Request $request, $id)
    {
        $record = CuratedRegister::findOrFail($id);

        return view('admin.curated-register.show', compact('record'));
    }

    public function apiActivityLog(Request $request, $id)
    {
        $record = CuratedRegister::findOrFail($id);

        $res = collect([
            [
                'id' => 1,
                'action' => 'created',
                'title' => 'Onboarding application created',
                'message' => null,
                'link' => null,
                'timestamp' => $record->created_at,
            ],
        ]);

        if ($record->email_verified_at) {
            $res->push([
                'id' => 3,
                'action' => 'email_verified_at',
                'title' => 'Applicant successfully verified email address',
                'message' => null,
                'link' => null,
                'timestamp' => $record->email_verified_at,
            ]);
        }

        $activities = CuratedRegisterActivity::whereRegisterId($record->id)->get();

        $idx = 4;
        $userResponses = collect([]);

        foreach ($activities as $activity) {
            $idx++;

            if ($activity->type === 'user_resend_email_confirmation') {
                continue;
            }
            if ($activity->from_user) {
                $userResponses->push($activity);

                continue;
            }
            $res->push([
                'id' => $idx,
                'aid' => $activity->id,
                'action' => $activity->type,
                'title' => $activity->from_admin ? 'Admin requested info' : 'User responded',
                'message' => $activity->message,
                'link' => $activity->adminReviewUrl(),
                'timestamp' => $activity->created_at,
            ]);
        }

        foreach ($userResponses as $ur) {
            $res = $res->map(function ($r) use ($ur) {
                if (! isset($r['aid'])) {
                    return $r;
                }
                if ($ur->reply_to_id === $r['aid']) {
                    $r['user_response'] = $ur;

                    return $r;
                }

                return $r;
            });
        }

        if ($record->is_approved) {
            $idx++;
            $res->push([
                'id' => $idx,
                'action' => 'approved',
                'title' => 'Application Approved',
                'message' => null,
                'link' => null,
                'timestamp' => $record->action_taken_at,
            ]);
        } elseif ($record->is_rejected) {
            $idx++;
            $res->push([
                'id' => $idx,
                'action' => 'rejected',
                'title' => 'Application Rejected',
                'message' => null,
                'link' => null,
                'timestamp' => $record->action_taken_at,
            ]);
        }

        return $res->reverse()->values();
    }

    public function apiMessagePreviewStore(Request $request, $id)
    {
        $record = CuratedRegister::findOrFail($id);

        return $request->all();
    }

    public function apiMessageSendStore(Request $request, $id)
    {
        $this->validate($request, [
            'message' => 'required|string|min:5|max:3000',
        ]);
        $record = CuratedRegister::findOrFail($id);
        abort_if($record->email_verified_at === null, 400, 'Cannot message an unverified email');
        $activity = new CuratedRegisterActivity;
        $activity->register_id = $record->id;
        $activity->admin_id = $request->user()->id;
        $activity->secret_code = Str::random(32);
        $activity->type = 'request_details';
        $activity->from_admin = true;
        $activity->message = $request->input('message');
        $activity->save();
        $record->is_awaiting_more_info = true;
        $record->user_has_responded = false;
        $record->save();
        Mail::to($record->email)->send(new CuratedRegisterRequestDetailsFromUser($record, $activity));

        return $request->all();
    }

    public function previewDetailsMessageShow(Request $request, $id)
    {
        $record = CuratedRegister::findOrFail($id);
        abort_if($record->email_verified_at === null, 400, 'Cannot message an unverified email');
        $activity = new CuratedRegisterActivity;
        $activity->message = $request->input('message');

        return new \App\Mail\CuratedRegisterRequestDetailsFromUser($record, $activity);
    }

    public function previewMessageShow(Request $request, $id)
    {
        $record = CuratedRegister::findOrFail($id);
        abort_if($record->email_verified_at === null, 400, 'Cannot message an unverified email');
        $record->message = $request->input('message');

        return new \App\Mail\CuratedRegisterSendMessage($record);
    }

    public function apiHandleReject(Request $request, $id)
    {
        $this->validate($request, [
            'action' => 'required|in:reject-email,reject-silent',
        ]);
        $action = $request->input('action');
        $record = CuratedRegister::findOrFail($id);
        abort_if($record->email_verified_at === null, 400, 'Cannot reject an unverified email');
        $record->is_rejected = true;
        $record->is_closed = true;
        $record->action_taken_at = now();
        $record->save();
        if ($action === 'reject-email') {
            Mail::to($record->email)->send(new CuratedRegisterRejectUser($record));
        }

        return [200];
    }

    public function apiHandleApprove(Request $request, $id)
    {
        $record = CuratedRegister::findOrFail($id);
        abort_if($record->email_verified_at === null, 400, 'Cannot reject an unverified email');
        $record->is_approved = true;
        $record->is_closed = true;
        $record->action_taken_at = now();
        $record->save();

        if (User::withTrashed()->whereEmail($record->email)->exists()) {
            return [200];
        }

        $user = User::create([
            'name' => $record->username,
            'username' => $record->username,
            'email' => $record->email,
            'password' => $record->password,
            'app_register_ip' => $record->ip_address,
            'email_verified_at' => now(),
            'register_source' => 'cur_onboarding',
        ]);

        Mail::to($record->email)->send(new CuratedRegisterAcceptUser($record));

        return [200];
    }

    public function templates(Request $request)
    {
        $templates = CuratedRegisterTemplate::paginate(10);

        return view('admin.curated-register.templates', compact('templates'));
    }

    public function templateCreate(Request $request)
    {
        return view('admin.curated-register.template-create');
    }

    public function templateEdit(Request $request, $id)
    {
        $template = CuratedRegisterTemplate::findOrFail($id);

        return view('admin.curated-register.template-edit', compact('template'));
    }

    public function templateEditStore(Request $request, $id)
    {
        $this->validate($request, [
            'name' => 'required|string|max:30',
            'content' => 'required|string|min:5|max:3000',
            'description' => 'nullable|sometimes|string|max:1000',
            'active' => 'sometimes',
        ]);
        $template = CuratedRegisterTemplate::findOrFail($id);
        $template->name = $request->input('name');
        $template->content = $request->input('content');
        $template->description = $request->input('description');
        $template->is_active = $request->boolean('active');
        $template->save();

        return redirect()->back()->with('status', 'Successfully updated template!');
    }

    public function templateDelete(Request $request, $id)
    {
        $template = CuratedRegisterTemplate::findOrFail($id);
        $template->delete();

        return redirect(route('admin.curated-onboarding.templates'))->with('status', 'Successfully deleted template!');
    }

    public function templateStore(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string|max:30',
            'content' => 'required|string|min:5|max:3000',
            'description' => 'nullable|sometimes|string|max:1000',
            'active' => 'sometimes',
        ]);
        CuratedRegisterTemplate::create([
            'name' => $request->input('name'),
            'content' => $request->input('content'),
            'description' => $request->input('description'),
            'is_active' => $request->boolean('active'),
        ]);

        return redirect(route('admin.curated-onboarding.templates'))->with('status', 'Successfully created new template!');
    }

    public function getActiveTemplates(Request $request)
    {
        $templates = CuratedRegisterTemplate::whereIsActive(true)
            ->orderBy('order')
            ->get()
            ->map(function ($tmp) {
                return [
                    'name' => $tmp->name,
                    'content' => $tmp->content,
                ];
            });

        return response()->json($templates);
    }
}
