<?php

namespace App\Util\ActivityPub\Writer;

use App\Util\ActivityPub\DiscoverActor;

class BaseWriter {

    protected $context = 'https://www.w3.org/ns/activitystreams';
    protected $activities = ['Announce','Create','Follow','Like'];
    protected $audiences = ['public', 'unlisted', 'private', 'direct'];
    protected $audience = 'public';
    protected $actors = ['Person'];
    protected $objects = ['Image','Note'];
    protected $verb;
    protected $sourceActivity;
    protected $activity;
    protected $profile;
    protected $to = [];
    protected $cc = [];
    protected $bcc = [];
    protected $response;
    protected $publishedAt;

    public static function build()
    {
        return (new self);
    }

    public function setContext($context)
    {
        $this->context = $context;
        return $this;
    }

    public function setActor($profile)
    {
        $this->actor = $profile;
        return $this;
    }

    public function setActorActivity($activity)
    {
        $this->activity = $activity;
        $this->setPublishedAt($activity->created_at->format('Y-m-d\Th:i:s\Z'));
        return $this;
    }

    public function setTo($audience)
    {
        $this->to = $audience;
        return $this;
    }

    public function setCc($audience)
    {
        $this->cc = $audience;
        return $this;
    }

    public function setBcc($audience)
    {
        $this->bcc = $audience;
        return $this;
    }

    public function setPublishedAt($timestamp)
    {
        $this->publishedAt = $timestamp;
        return $this;
    }

    public function audience($audience)
    {
        $this->setAudience($audience);
        $this->buildAudience();
        return $this;
    }

    public function setAudience($audience)
    {
        if(in_array($audience, $this->audience)) {
            $this->audience = $audience;
        }
        return $this;
    }

    public function buildAudience()
    {
        switch ($this->audience) {
          case 'public':
            $this->to = [
              $this->context . '#Public'
            ];
            $this->cc = [
              $this->actor->permalink('/followers')
            ];
            break;

          case 'unlisted':
            $this->to = [
              $this->actor->permalink('/followers')
            ];
            $this->cc = [
              $this->context . '#Public'
            ];
            break;

          case 'private':
            $this->to = [
              $this->actor->permalink('/followers')
            ];
            break;
          
          default:
            # code...
            break;
        }
        return $this;
    }

    public function get()
    {
        return $this->getJson();
    }

    public function getJson()
    {
        return json_encode($this->response);
    }

    public function getArray()
    {
        return $this->response;
    }
}