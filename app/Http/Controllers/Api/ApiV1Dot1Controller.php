<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use App\Status;
use App\Report;
use App\Profile;

class ApiV1Dot1Controller extends Controller
{
    protected $fractal;

    public function __construct()
    {
        $this->fractal = new Fractal\Manager();
        $this->fractal->setSerializer(new ArraySerializer());
    }

    public function json($res, $code = 200, $headers = [])
    {
        return response()->json($res, $code, $headers, JSON_UNESCAPED_SLASHES);
    }

    public function error($msg, $code = 400, $extra = [], $headers = [])
    {
        $res = [
            "msg" => $msg,
            "code" => $code
        ];
        return response()->json(array_merge($res, $extra), $code, $headers, JSON_UNESCAPED_SLASHES);
    }

    public function report(Request $request)
    {
        $user = $request->user();

        abort_if(!$user, 403);
        abort_if($user->status != null, 403);

        $report_type = $request->input('report_type');
        $object_id = $request->input('object_id');
        $object_type = $request->input('object_type');

        $types = [
            'spam',
            'sensitive',
            'abusive',
            'underage',
            'violence',
            'copyright',
            'impersonation',
            'scam',
            'terrorism'
        ];

        if (!$report_type || !$object_id || !$object_type) {
            return $this->error("Invalid or missing parameters", 400, ["error_code" => "ERROR_INVALID_PARAMS"]);
        }

        if (!in_array($report_type, $types)) {
            return $this->error("Invalid report type", 400, ["error_code" => "ERROR_TYPE_INVALID"]);
        }

        if ($object_type === "user" && $object_id == $user->profile_id) {
            return $this->error("Cannot self report", 400, ["error_code" => "ERROR_NO_SELF_REPORTS"]);
        }

        $rpid = null;

        switch ($object_type) {
            case 'post':
                $object = Status::find($object_id);
                if (!$object) {
                    return $this->error("Invalid object id", 400, ["error_code" => "ERROR_INVALID_OBJECT_ID"]);
                }
                $object_type = 'App\Status';
                $exists = Report::whereUserId($user->id)
                    ->whereObjectId($object->id)
                    ->whereObjectType('App\Status')
                    ->count();

                $rpid = $object->profile_id;
            break;

            case 'user':
                $object = Profile::find($object_id);
                if (!$object) {
                    return $this->error("Invalid object id", 400, ["error_code" => "ERROR_INVALID_OBJECT_ID"]);
                }
                $object_type = 'App\Profile';
                $exists = Report::whereUserId($user->id)
                    ->whereObjectId($object->id)
                    ->whereObjectType('App\Profile')
                    ->count();
                $rpid = $object->id;
            break;

            default:
                return $this->error("Invalid report type", 400, ["error_code" => "ERROR_REPORT_OBJECT_TYPE_INVALID"]);
            break;
      }

        if ($exists !== 0) {
            return $this->error("Duplicate report", 400, ["error_code" => "ERROR_REPORT_DUPLICATE"]);
        }

        if ($object->profile_id == $user->profile_id) {
            return $this->error("Cannot self report", 400, ["error_code" => "ERROR_NO_SELF_REPORTS"]);
        }

        $report = new Report;
        $report->profile_id = $user->profile_id;
        $report->user_id = $user->id;
        $report->object_id = $object->id;
        $report->object_type = $object_type;
        $report->reported_profile_id = $rpid;
        $report->type = $report_type;
        $report->save();

        $res = [
            "msg" => "Successfully sent report",
            "code" => 200
        ];
        return $this->json($res);
    }
}
