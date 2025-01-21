@component('mail::message')
Hello **{{'@'.$verify->username}}**,


We are excited to inform you that your account has been successfully activated!

Your journey into the world of visual storytelling begins now, and we can’t wait to see the incredible content you’ll create and share.

<x-mail::button :url="url('/login?email=' . $verify->email)" color="success">
<strong>Sign-in to your account</strong>
</x-mail::button>

Here’s what you can do next:

<x-mail::panel>
**Personalize Your Profile**: Customize your profile to reflect your personality or brand.

**Start Sharing**: Post your first photo or album and share your unique perspective with the world.

**Engage with the Community**: Follow other users, like and comment on posts, and become an active member of our vibrant community.

**Explore**: Discover amazing content from a diverse range of users and hashtags.
</x-mail::panel>

Need help getting started? Visit our [Help Center]({{url('site/help')}}) for tips, tutorials, and FAQs. Remember, our community thrives on respect and creativity, so please familiarize yourself with our [Community Guidelines]({{url('site/kb/community-guidelines')}}).

If you have any questions or need assistance, feel free to reach out to [our support team]({{url('/site/contact')}}).

Happy posting, and once again, welcome to Pixelfed!

Warm regards,<br>
<strong>{{ config('pixelfed.domain.app') }}</strong>

<br>
<hr>
<p style="font-size:10pt;">This is an automated message, please be aware that replies to this email cannot be monitored or responded to.</p>
@endcomponent
