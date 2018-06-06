<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::domain(config('pixelfed.domain.admin'))->group(function() {
  Route::redirect('/', '/dashboard');
  Route::redirect('timeline', config('app.url').'/timeline');
  Route::get('dashboard', 'AdminController@home')->name('admin.home');
  Route::redirect('statuses', '/statuses/list');
  Route::get('statuses/list', 'AdminController@statuses')->name('admin.statuses');
  Route::get('statuses/show/{id}', 'AdminController@showStatus');
  Route::redirect('users', '/users/list');
  Route::get('users/list', 'AdminController@users')->name('admin.users');
  Route::redirect('media', '/media/list');
  Route::get('media/list', 'AdminController@media')->name('admin.media');
});

Route::domain(config('pixelfed.domain.app'))->group(function() {

  Route::view('/', 'welcome');

  Auth::routes();

  Route::get('.well-known/webfinger', 'FederationController@webfinger');
  Route::get('.well-known/nodeinfo', 'FederationController@nodeinfoWellKnown');

  Route::get('/home', 'HomeController@index')->name('home');


  Route::get('discover', 'DiscoverController@home')->name('discover');

  Route::get('search/hashtag/{tag}', function($tag) {
    return redirect('/discover/tags/'.$tag);
  });

  Route::group(['prefix' => 'api'], function() {
    Route::get('search/{tag}', 'SearchController@searchAPI')
          ->where('tag', '[A-Za-z0-9]+');
    Route::get('nodeinfo/2.0.json', 'FederationController@nodeinfo');
    Route::get('v1/likes', 'ApiController@hydrateLikes');
  });

  Route::get('discover/tags/{hashtag}', 'DiscoverController@showTags');

  Route::group(['prefix' => 'i'], function() {
    Route::redirect('/', '/');
    Route::get('remote-follow', 'FederationController@remoteFollow')->name('remotefollow');
    Route::post('remote-follow', 'FederationController@remoteFollowStore');
    Route::post('comment', 'CommentController@store');
    Route::post('delete', 'StatusController@delete');
    Route::post('like', 'LikeController@store');
    Route::post('follow', 'FollowerController@store');
    Route::post('bookmark', 'BookmarkController@store');
    Route::get('lang/{locale}', 'SiteController@changeLocale');

    Route::group(['prefix' => 'report'], function() {
      Route::get('/', 'ReportController@showForm')->name('report.form');
      Route::get('not-interested', 'ReportController@notInterestedForm')->name('report.not-interested');
      Route::get('spam', 'ReportController@spamForm')->name('report.spam');
      Route::get('spam/comment', 'ReportController@spamCommentForm')->name('report.spam.comment');
      Route::get('spam/post', 'ReportController@spamPostForm')->name('report.spam.post');
      Route::get('spam/profile', 'ReportController@spamProfileForm')->name('report.spam.profile');
    });
  });

  Route::group(['prefix' => 'account'], function() {
    Route::redirect('/', '/');
    Route::get('activity', 'AccountController@notifications')->name('notifications');
  });

  Route::group(['prefix' => 'settings'], function() {
    Route::redirect('/', '/settings/home');
    Route::get('home', 'SettingsController@home')->name('settings');
    Route::post('home', 'SettingsController@homeUpdate');
    Route::get('avatar', 'SettingsController@avatar')->name('settings.avatar');
    Route::get('password', 'SettingsController@password')->name('settings.password');
    Route::post('password', 'SettingsController@passwordUpdate');
    Route::get('email', 'SettingsController@email')->name('settings.email');
    Route::get('notifications', 'SettingsController@notifications')->name('settings.notifications');
    Route::get('privacy', 'SettingsController@privacy')->name('settings.privacy');
    Route::get('security', 'SettingsController@security')->name('settings.security');
    Route::get('applications', 'SettingsController@applications')->name('settings.applications');
    Route::get('data-export', 'SettingsController@dataExport')->name('settings.dataexport');
    Route::get('import', 'SettingsController@dataImport')->name('settings.import');
    Route::get('import/instagram', 'SettingsController@dataImportInstagram')->name('settings.import.ig');
    Route::get('developers', 'SettingsController@developers')->name('settings.developers');
  });

  Route::group(['prefix' => 'timeline'], function() {
    Route::get('/', 'TimelineController@personal')->name('timeline.personal');
    Route::post('/', 'StatusController@store');
    Route::get('public', 'TimelineController@local')->name('timeline.public');
    Route::post('public', 'StatusController@store');
  });

  Route::group(['prefix' => 'users'], function() {
    Route::redirect('/', '/');
    Route::get('{user}.atom', 'ProfileController@showAtomFeed');
    Route::get('{username}/outbox', 'FederationController@userOutbox');
    Route::get('{user}', function($user) {
      return redirect('/@'.$user);
    });
  });

  Route::group(['prefix' => 'site'], function() {
    Route::redirect('/', '/');
    Route::view('about', 'site.about')->name('site.about');
    Route::view('features', 'site.features')->name('site.features');
    Route::view('help', 'site.help')->name('site.help');
    Route::view('fediverse', 'site.fediverse')->name('site.fediverse');
    Route::view('open-source', 'site.opensource')->name('site.opensource');
    Route::view('banned-instances', 'site.bannedinstances')->name('site.bannedinstances');
    Route::view('terms', 'site.terms')->name('site.terms');
    Route::view('privacy', 'site.privacy')->name('site.privacy');
    Route::view('platform', 'site.platform')->name('site.platform');
    Route::view('libraries', 'site.libraries')->name('site.libraries');
    Route::view('language', 'site.language')->name('site.language');
  });

  Route::get('p/{username}/{id}/c/{cid}', 'CommentController@show');
  Route::get('p/{username}/{id}', 'StatusController@show');
  Route::get('{username}/saved', 'ProfileController@savedBookmarks');
  Route::get('{username}/followers', 'ProfileController@followers');
  Route::get('{username}/following', 'ProfileController@following');
  Route::get('{username}', 'ProfileController@show');

});
