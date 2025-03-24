<?php

return [

    'about'             => 'About',
    'help'              => 'Help',
    'language'          => 'Language',
    'fediverse'         => 'Fediverse',
    'opensource'        => 'Open Source',
    'terms'             => 'Terms',
    'privacy'           => 'Privacy',
    'l10nWip'           => 'We’re still working on localization support',
    'currentLocale'     => 'Current locale',
    'selectLocale'      => 'Select one of the supported languages',
    'contact'           => 'Contact',
    'contact-us'        => 'Contact Us',
    'places'            => 'Places',
    'profiles'          => 'Profiles',

    // site/contact
    'you_can_contact_the_admins'                            => 'You can contact the admins',
    'by_using_the_form_below'                               => 'by using the form below',
    'or'                                                    => 'or',
    'by_sending_an_email_to'                                => 'by sending an email to',
    'the_admins_have_not_set_a_contact_email_address'       => 'The admins have not set a contact email address',
    'Message'                                               => 'Message',
    'request_response_from_admins'                          => 'Request response from admins',
    'Submit'                                                => 'Submit',
    'log_in_to_send_a_message'                              => 'log in to send a message',
    'Please'                                                => 'Please',

    // site/about
    'photo_sharing_for_everyone'                            => 'Photo Sharing. For Everyone',
    'pixelfed_is_an_image_sharing_platform_etc'             => 'Pixelfed is an image sharing platform, an ethical alternative to centralized platforms.', // this is actually never used because it's a fallback for config_cache('app.description') and config_cache('app.short_description') which seem to be impossible to set to empty when saved via /admin/settings?t=branding
    'feature_packed'                                        => 'Feature Packed.',
    'the_best_for_the_brightest'                            => 'The best for the brightest 📸',
    'albums'                                                => 'Albums',
    'share_posts_with_up_to'                                => 'Share posts with up to',
    'photos'                                                => 'photos',
    'comments'                                              => 'Comments',
    'comment_on_a_post_or_send_a_reply'                     => 'Comment on a post, or send a reply',
    'collections'                                           => 'Collections',
    'organize_and_share_collections_of_multiple_posts'      => 'Organize and share collections of multiple posts',
    'discover'                                              => 'Discover',
    'explore_categories_hashtags_and_topics'                => 'Explore categories, hashtags and topics',
    'photo_filters'                                         => 'Photo Filters',
    'add_a_special_touch_to_your_photos'                    => 'Add a special touch to your photos',
    'stories'                                               => 'Stories',
    'share_moments_with_your_followers_that_disappear_etc'  => 'Share moments with your followers that disappear after 24 hours',
    'people_have_shared'                                    => 'people have shared',
    'photos_and_videos_on'                                  => 'photos and videos on',
    'sign_up_today'                                         => 'Sign up today',
    'and_join_our_community_of_photographers_from_etc'      => 'and join our community of photographers from around the world.',

    // site/fediverse
    'is_a_portmanteau_of_federation_and_universe_etc'       => 'is a portmanteau of “federation” and “universe”. It is a common, informal name for a federation of social network servers, specializing in different types of media.',
    'supported_fediverse_projects'                          => 'Supported Fediverse Projects',
    'some_of_the_better_known_fediverse_projects_include'   => 'Some of the better known fediverse projects include:',
    'a_federated_microblogging_alternative'                 => 'A federated microblogging alternative.',

    // site/opensource
    'the_software_that_powers_this_website_is_called'       => 'The software that powers this website is called',
    'and_anyone_can'                                        => 'and anyone can',
    'download'                                              => 'download',
    'opensource.or'                                         => 'or',
    'view'                                                  => 'view',
    'the_source_code_and_run_their_own_instance'            => 'the source code and run their own instance!',
    'open_source_in_pixelfed'                               => 'Open source in Pixelfed',
];