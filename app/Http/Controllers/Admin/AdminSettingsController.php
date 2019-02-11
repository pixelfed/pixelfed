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
      $path = storage_path('app/PixelFed');
      $files = new \DirectoryIterator($path);
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
      $databaseSum = Cache::remember('admin:settings:storage:db:storageUsed', 360, function() {
        $q = 'SELECT sum(ROUND(((data_length + index_length)), 0)) AS size FROM information_schema.TABLES WHERE table_schema = ?';
        $db = config('database.default');
        $db = config("database.connections.{$db}.database");
        return DB::select($q, [$db])[0]->size;
      });
      $mediaSum = Cache::remember('admin:settings:storage:media:storageUsed', 360, function() {
        return Media::sum('size');
      });
      $backupSum = Cache::remember('admin:settings:storage:backups:storageUsed', 360, function() {
        $dir = storage_path('app/'.config('app.name'));
        $size = 0;
        foreach (glob(rtrim($dir, '/').'/*', GLOB_NOSORT) as $each) {
          $size += is_file($each) ? filesize($each) : folderSize($each);
        }
        return $size;
      });
      $storage = new \StdClass;
      $storage->total = disk_total_space(base_path());
      $storage->free = disk_free_space(base_path());
      $storage->prettyTotal = PrettyNumber::size($storage->total, false, false);
      $storage->prettyFree = PrettyNumber::size($storage->free, false, false);
      $storage->percentFree = ceil($storage->free / $storage->total * 100);
      $storage->percentUsed = ceil(100 - $storage->percentFree);
      $storage->media = [
        'used' => $mediaSum,
        'prettyUsed' => PrettyNumber::size($mediaSum),
        'percentUsed' => ceil($mediaSum / $storage->total * 100)
      ];
      $storage->backups = [
        'used' => $backupSum
      ];
      $storage->database = [
        'used' => $databaseSum
      ];
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
      'mysql' => DB::select( DB::raw("select version()") )[0]->{'version()'},
      'php' => phpversion(),
      'redis' => explode(' ',exec('redis-cli -v'))[1],
    ];
    return view('admin.settings.system', compact('sys'));
  }
}