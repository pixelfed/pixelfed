# Username validation boilerplate
`new ValidUsername,`
replaces
```
              function ($attribute, $value, $fail) {
                    $dash = substr_count($value, '-');
                    $underscore = substr_count($value, '_');
                    $period = substr_count($value, '.');

                    if (Str::endsWith($value, ['.php', '.js', '.css'])) {
                        return $fail('Username is invalid.');
                    }

                    if (($dash + $underscore + $period) > 1) {
                        return $fail('Username is invalid. Can only contain one dash (-), period (.) or underscore (_).');
                    }

                    if (! ctype_alnum($value[0])) {
                        return $fail('Username is invalid. Must start with a letter or number.');
                    }

                    if (! ctype_alnum($value[strlen($value) - 1])) {
                        return $fail('Username is invalid. Must end with a letter or number.');
                    }

                    $val = str_replace(['_', '.', '-'], '', $value);
                    if (! ctype_alnum($val)) {
                        return $fail('Username is invalid. Username must be alpha-numeric and may contain dashes (-), periods (.) and underscores (_).');
                    }

                    $restricted = RestrictedNames::get();
                    if (in_array(strtolower($value), array_map('strtolower', $restricted))) {
                        return $fail('Username cannot be used.');
                    }
                },
```

# Notification boilerplate
`NotificationService::createNotification($recipient->id, $profile->id, 'dm', $dm->id, DirectMessage::class);`
`NotificationService::firstOrCreateNotification($parent->profile_id, $actor->id, 'share', $parent->id, Status::class);`
replaces repeated
```
            $notification = new Notification;
            $notification->profile_id = $recipient->id;
            $notification->actor_id = $profile->id;
            $notification->action = 'dm';
            $notification->item_id = $dm->id;
            $notification->item_type = DirectMessage::class;
            $notification->save();
```

# fractal boilerplate for items and collection
`$activity = FractalService::item($follow, new AcceptFollow);`
`return FractalService::collection($media, new MediaTransformer);`

replaces
```
            $fractal = new Fractal\Manager;
            $fractal->setSerializer(new ArraySerializer);
            $resource = new Fractal\Resource\Item($follow, new AcceptFollow);
            $activity = $fractal->createData($resource)->toArray();
```
