<?php

namespace Tests\Unit\ActivityPub;

use App\Util\ActivityPub\Helpers;
use Tests\TestCase;

class NoteAttachmentTest extends TestCase
{
    protected array $pixelfed;
    protected array $pleroma;
    protected array $mastodon;
    protected array $invalidType;
    protected array $invalidMime;

    public function setUp(): void
    {
        parent::setUp();

        $this->pixelfed = json_decode('{"@context":["https://www.w3.org/ns/activitystreams","https://w3id.org/security/v1",{"sc":"http://schema.org#","Hashtag":"as:Hashtag","sensitive":"as:sensitive","commentsEnabled":"sc:Boolean","capabilities":{"announce":{"@type":"@id"},"like":{"@type":"@id"},"reply":{"@type":"@id"}},"toot":"http://joinmastodon.org/ns#","Emoji":"toot:Emoji"}],"id":"https://pixelfed.social/p/dansup/2","type":"Note","summary":null,"content":"This was my first IG post too. #mazda #zoomzoom","inReplyTo":null,"published":"2018-06-01T05:16:51+00:00","url":"https://pixelfed.social/p/dansup/2","attributedTo":"https://pixelfed.social/users/dansup","to":["https://www.w3.org/ns/activitystreams#Public"],"cc":["https://pixelfed.social/users/dansup/followers"],"sensitive":false,"attachment":[{"type":"Image","mediaType":"image/jpeg","url":"https://pixelfed.social/storage/m/e64c75f2f4e9a621bb7c43ec9b04a165add6493b/b87fd76cbce4a613a9b9cba20f354709e67caf25/B4SHeEqWsj5t7qCIRzm7nvfCAtm31J8o12Ji1A2x.jpeg","name":null}],"tag":[{"type":"Hashtag","href":"https://pixelfed.social/discover/tags/mazda","name":"#mazda"},{"type":"Hashtag","href":"https://pixelfed.social/discover/tags/zoomzoom","name":"#zoomzoom"}],"commentsEnabled":false,"capabilities":{"announce":"https://www.w3.org/ns/activitystreams#Public","like":"https://www.w3.org/ns/activitystreams#Public","reply":null},"location":null}', true, 9);

        $this->pleroma = json_decode('{"@context":"https://www.w3.org/ns/activitystreams","actor":"https://pleroma.site/users/pixeldev","cc":["https://pleroma.site/users/pixeldev/followers"],"context":"https://pleroma.site/contexts/cbe919c6-238e-4e5d-9065-fcb3c312b56a","context_id":8651628,"id":"https://pleroma.site/activities/65b2c43f-f33e-438e-b141-4e2047b43012","object":{"actor":"https://pleroma.site/users/pixeldev","announcement_count":2,"announcements":["https://playvicious.social/users/jalcine","https://mastodon.social/users/dansup"],"attachment":[{"mediaType":"image/png","name":"Screen Shot 2018-09-08 at 10.59.38 PM.png","type":"Document","url":"https://s3.wasabisys.com/pleroma-site/1c49e1f9-1187-404d-a063-1b37ecec44e9/Screen Shot 2018-09-08 at 10.59.38 PM.png"},{"mediaType":"image/jpeg","name":"archer-danger-zone.jpg","type":"Document","url":"https://s3.wasabisys.com/pleroma-site/cd70cdb6-0148-4dcb-bac6-11fd4aa59834/archer-danger-zone.jpg"}],"attributedTo":"https://pleroma.site/users/pixeldev","cc":["https://pleroma.site/users/pixeldev/followers"],"content":"New middleware for specific actions, acts like sudo requiring periodic password verification. <a href=\'https://pleroma.site/tag/dangerzone\' rel=\'tag\'>#dangerZone</a>","context":"https://pleroma.site/contexts/cbe919c6-238e-4e5d-9065-fcb3c312b56a","context_id":8651628,"conversation":"https://pleroma.site/contexts/cbe919c6-238e-4e5d-9065-fcb3c312b56a","emoji":{},"id":"https://pleroma.site/objects/b7576ec9-ae2b-4076-a426-0d8a65b23876","published":"2018-09-09T05:05:53.763752Z","sensitive":false,"summary":"","tag":[{"href":"https://pleroma.site/tags/dangerzone","name":"#dangerzone","type":"Hashtag"}],"to":["https://www.w3.org/ns/activitystreams#Public"],"type":"Note"},"published":"2018-09-09T05:05:53.749866Z","to":["https://www.w3.org/ns/activitystreams#Public"],"type":"Create"}', true, 9);

        $this->mastodon = json_decode('{"id":"https://mastodon.social/users/dansup/statuses/100889802384218791/activity","type":"Create","actor":"https://mastodon.social/users/dansup","published":"2018-10-13T18:43:33Z","to":["https://www.w3.org/ns/activitystreams#Public"],"cc":["https://mastodon.social/users/dansup/followers"],"object":{"id":"https://mastodon.social/users/dansup/statuses/100889802384218791","type":"Note","summary":null,"inReplyTo":null,"published":"2018-10-13T18:43:33Z","url":"https://mastodon.social/@dansup/100889802384218791","attributedTo":"https://mastodon.social/users/dansup","to":["https://www.w3.org/ns/activitystreams#Public"],"cc":["https://mastodon.social/users/dansup/followers"],"sensitive":false,"atomUri":"https://mastodon.social/users/dansup/statuses/100889802384218791","inReplyToAtomUri":null,"conversation":"tag:mastodon.social,2018-10-13:objectId=59103420:objectType=Conversation","content":"<p>Good Morning! <a href=\"https://mastodon.social/tags/coffee\" class=\"mention hashtag\" rel=\"tag\">#<span>coffee</span></a></p>","contentMap":{"en":"<p>Good Morning! <a href=\"https://mastodon.social/tags/coffee\" class=\"mention hashtag\" rel=\"tag\">#<span>coffee</span></a></p>"},"attachment":[{"type":"Document","mediaType":"image/jpeg","url":"https://files.mastodon.social/media_attachments/files/007/110/573/original/96a196885a77c9a4.jpg","name":null}],"tag":[{"type":"Hashtag","href":"https://mastodon.social/tags/coffee","name":"#coffee"}]}}', true, 9);

        $this->invalidType = json_decode('{"id":"https://mastodon.social/users/dansup/statuses/100889802384218791/activity","type":"Create","actor":"https://mastodon.social/users/dansup","published":"2018-10-13T18:43:33Z","to":["https://www.w3.org/ns/activitystreams#Public"],"cc":["https://mastodon.social/users/dansup/followers"],"object":{"id":"https://mastodon.social/users/dansup/statuses/100889802384218791","type":"Note","summary":null,"inReplyTo":null,"published":"2018-10-13T18:43:33Z","url":"https://mastodon.social/@dansup/100889802384218791","attributedTo":"https://mastodon.social/users/dansup","to":["https://www.w3.org/ns/activitystreams#Public"],"cc":["https://mastodon.social/users/dansup/followers"],"sensitive":false,"atomUri":"https://mastodon.social/users/dansup/statuses/100889802384218791","inReplyToAtomUri":null,"conversation":"tag:mastodon.social,2018-10-13:objectId=59103420:objectType=Conversation","content":"<p>Good Morning! <a href=\"https://mastodon.social/tags/coffee\" class=\"mention hashtag\" rel=\"tag\">#<span>coffee</span></a></p>","contentMap":{"en":"<p>Good Morning! <a href=\"https://mastodon.social/tags/coffee\" class=\"mention hashtag\" rel=\"tag\">#<span>coffee</span></a></p>"},"attachment":[{"type":"NotDocument","mediaType":"image/jpeg","url":"https://files.mastodon.social/media_attachments/files/007/110/573/original/96a196885a77c9a4.jpg","name":null}],"tag":[{"type":"Hashtag","href":"https://mastodon.social/tags/coffee","name":"#coffee"}]}}', true, 9);

        $this->invalidMime = json_decode('{"id":"https://mastodon.social/users/dansup/statuses/100889802384218791/activity","type":"Create","actor":"https://mastodon.social/users/dansup","published":"2018-10-13T18:43:33Z","to":["https://www.w3.org/ns/activitystreams#Public"],"cc":["https://mastodon.social/users/dansup/followers"],"object":{"id":"https://mastodon.social/users/dansup/statuses/100889802384218791","type":"Note","summary":null,"inReplyTo":null,"published":"2018-10-13T18:43:33Z","url":"https://mastodon.social/@dansup/100889802384218791","attributedTo":"https://mastodon.social/users/dansup","to":["https://www.w3.org/ns/activitystreams#Public"],"cc":["https://mastodon.social/users/dansup/followers"],"sensitive":false,"atomUri":"https://mastodon.social/users/dansup/statuses/100889802384218791","inReplyToAtomUri":null,"conversation":"tag:mastodon.social,2018-10-13:objectId=59103420:objectType=Conversation","content":"<p>Good Morning! <a href=\"https://mastodon.social/tags/coffee\" class=\"mention hashtag\" rel=\"tag\">#<span>coffee</span></a></p>","contentMap":{"en":"<p>Good Morning! <a href=\"https://mastodon.social/tags/coffee\" class=\"mention hashtag\" rel=\"tag\">#<span>coffee</span></a></p>"},"attachment":[{"type":"Document","mediaType":"image/webp","url":"https://files.mastodon.social/media_attachments/files/007/110/573/original/96a196885a77c9a4.jpg","name":null}],"tag":[{"type":"Hashtag","href":"https://mastodon.social/tags/coffee","name":"#coffee"}]}}', true, 9);
    }

    public function testPixelfed()
    {
        $valid = Helpers::verifyAttachments($this->pixelfed);
        $this->assertTrue($valid);
    }

    public function testMastodon()
    {
        $valid = Helpers::verifyAttachments($this->mastodon);
        $this->assertTrue($valid);
    }

    public function testInvalidAttachmentType()
    {
        $valid = Helpers::verifyAttachments($this->invalidType);
        $this->assertFalse($valid);
    }

    public function testInvalidMimeType()
    {
        $valid = Helpers::verifyAttachments($this->invalidMime);
        $this->assertFalse($valid);
    }
}

