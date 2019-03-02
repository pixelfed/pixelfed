<?php

namespace App\Http\Controllers\Admin;

use Artisan, Cache, DB;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\{Comment, Like, Media, Page, Profile, Report, Status, User};
use App\Http\Controllers\Controller;
use Jackiedo\DotenvEditor\Facades\DotenvEditor;
use App\Util\Lexer\PrettyNumber;

trait AdminSettingsController
{
    public function settings(Request $request)
    {
      return view('admin.settings.home');
    }

    public function settingsBackups(Request $request)
    {
      $path = storage_path('app/'.config('app.name'));
      $files = is_dir($path) ? new \DirectoryIterator($path) : [];
      return view('admin.settings.backups', compact('files'));
    }

    public function settingsConfig(Request $request, DotenvEditor $editor)
    {
      return view('admin.settings.config', compact('editor'));
    }

    public function settingsMaintenance(Request $request)
    {
      return view('admin.settings.maintenance');
    }

    public function settingsStorage(Request $request)
    {
      $storage = [];
      return view('admin.settings.storage', compact('storage'));
    }

    public function settingsFeatures(Request $request)
    {
      return view('admin.settings.features');
    }
    
	public function settingsHomeStore(Request $request)
	{
		$this->validate($request, [
			'APP_NAME' => 'required|string',
		]);
		Artisan::call('config:clear');
		DotenvEditor::setKey('APP_NAME', $request->input('APP_NAME'));
		DotenvEditor::save();
		return redirect()->back();
	}

	public function settingsPages(Request $request)
	{
    $pages = Page::orderByDesc('updated_at')->paginate(10);
		return view('admin.pages.home', compact('pages'));
	}

	public function settingsPageEdit(Request $request)
	{
		return view('admin.pages.edit');
	}

  public function settingsSystem(Request $request)
  {
    $sys = [
      'pixelfed' => config('pixelfed.version'),
      'php' => phpversion(),
      'laravel' => app()->version(),
    ];
    switch (config('database.default')) {
      case 'pgsql':
        $sys['database'] = [
          'name' => 'Postgres',
          'version' => explode(' ', DB::select(DB::raw('select version();'))[0]->version)[1]
        ];
        break;

      case 'mysql':
        $sys['database'] = [
          'name' => 'MySQL',
          'version' => DB::select( DB::raw("select version()") )[0]->{'version()'}
        ];
        break;
      
      default:
        $sys['database'] = [
          'name' => 'Unknown',
          'version' => '?'
        ];
        break;
    }
    return view('admin.settings.system', compact('sys'));
  }
}