# TODO / Future Improvements

Running list of follow-up work identified while debugging the media lifecycle.

## Media / Status lifecycle

- [ ] **Centralize status media teardown.** The detach-then-dispatch media
  cleanup is currently copy-pasted in `StatusDelete`, `RemoteStatusDelete`, and
  `DeleteRemoteStatusPipeline`. Extract a single helper (e.g.
  `MediaStorageService::deleteStatusMedia(Status $status)`) and call it from
  every status-delete path so the logic can't drift out of sync.

- [ ] **Fix DirectMessageController media leak.** `DirectMessageController`
  deletes DM statuses via `forceDeleteQuietly()`, which bypasses model events
  AND never dispatches `MediaDeletePipeline`. DM media rows and their files
  (local/S3/HLS) leak. Route it through the centralized helper above.

- [ ] **Do NOT use a DB FK `ON DELETE CASCADE` for media→status.** Reasons:
  both models use `SoftDeletes` (soft delete is an UPDATE, cascade only fires on
  hard DELETE), and a row-level cascade would orphan the actual stored files
  (local disk, S3 objects, HLS `.m3u8`/`.ts` segments) since all file cleanup
  lives in `MediaDeletePipeline`, not the DB. Keep teardown in application code.

- [ ] **Optional defense-in-depth:** a `Status::deleting` observer hook that
  calls the centralized teardown, so future delete paths are covered
  automatically. Note `forceDeleteQuietly()` callers skip events and must still
  call the helper explicitly.

## StatusRemoteUpdatePipeline (remote edits)

- [ ] **Don't orphan media on remote edit when re-fetch fails.**
  `StatusRemoteUpdatePipeline::updateMedia()` detaches all media
  (`status_id = null`) then re-imports; if the new attachment HEAD requests 404
  (origin deleted the media) nothing is re-attached and the rows are left
  orphaned. Only detach media that is actually being replaced, or keep existing
  media when the incoming set resolves to nothing.

## media:gc (GarbageCollectorMedia)

- [ ] **Re-check `status_id` immediately before dispatch.** `media:gc` selects a
  batch with `->get()` then dispatches per row; a row can be re-attached between
  select and dispatch. Re-read `status_id` right before dispatching.

- [ ] **Consider a `remote_media` guard / policy.** Federated media lifecycle is
  owned by the remote instance; decide whether `media:gc` should treat it
  differently from local orphan uploads.

## Diagnostics / tooling

- [ ] Roll back the temporary `MEDIA-TRACE:` debug logging
  (branch `debug/media-lifecycle-trace-logs`) once diagnosis is complete.
- [ ] Consider additional `media:maintenance --scope` routines (e.g. missing
  local files, stale cloud URLs, mime backfill).
