<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function showForm(Request $request)
    {
      return view('report.form');
    }

    public function notInterestedForm(Request $request)
    {
      return view('report.not-interested');
    }

    public function spamForm(Request $request)
    {
      return view('report.spam');
    }

    public function spamCommentForm(Request $request)
    {
      return view('report.spam.comment');
    }

    public function spamPostForm(Request $request)
    {
      return view('report.spam.post');
    }

    public function spamProfileForm(Request $request)
    {
      return view('report.spam.profile');
    }
}
