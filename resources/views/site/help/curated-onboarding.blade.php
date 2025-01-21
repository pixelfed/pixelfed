@extends('site.help.partial.template', ['breadcrumb'=>'Curated Onboarding'])

@section('section')
<div class="title">
    <h3 class="font-weight-bold">Curated Onboarding</h3>
</div>
<hr>
@if((bool) config_cache('instance.curated_registration.enabled') == false)
<div class="card bg-danger mb-3">
    <div class="card-body">
        @if((bool) config_cache('pixelfed.open_registration'))
        <p class="mb-0 text-white font-weight-bold">Curated Onboarding is not available on this server, however anyone can join.</p>
        <hr>
        <p class="mb-0 text-center"><a href="/register" class="btn btn-light font-weight-bold rounded-pill">Create New Account</a></p>
        @else
        <p class="mb-0 text-white font-weight-bold">Curated Onboarding is not available on this server.</p>
        @endif
    </div>
</div>
@endif
<p class="lead font-weight-bold">Curated Onboarding is our innovative approach to ensure each new member is a perfect fit for our community.</p>
<p class="lead font-weight-light">This process goes beyond the usual sign-up routine. It's a thoughtful method to understand each applicant's intentions and aspirations within our platform.</p>
<p class="lead font-weight-light">If you're excited to be a part of a platform that values individuality, creativity, and community, we invite you to apply to join our community. Share with us your story, and let's embark on this visual journey together!</p>
@if((bool) config_cache('instance.curated_registration.enabled') && !request()->user())
<p class="text-center pt-3">
    <a class="btn btn-outline-primary rounded-pill btn-lg font-weight-bold px-5" href="{{ route('auth.curated-onboarding') }}">Apply to Join <i class="far fa-arrow-right ml-2"></i></a>
</p>
@endif
<hr class="my-5">
<h5 class="text-center text-muted font-weight-light my-5">How does Curated Onboarding work?</h5>
<ol>
    <li>
        <p class="h5 font-weight-bold mb-2">Application Submission</p>
        <p style="font-size: 16px;line-height: 1.7;">Start your journey by providing your username and email, along with a personal note about why you're excited to join Pixelfed. This insight into your interests and aspirations helps us get to know you better.</p>
    </li>
    <hr class="my-5">
    <li>
        <p class="h5 font-weight-bold mb-2">Admin Review and Interaction</p>
        <p style="font-size: 16px;line-height: 1.7;">Our team carefully reviews each application, assessing your fit within our community. If we're intrigued but need more information, we'll reach out directly. You'll receive an email with a link to a special form where you can view our request and respond in detail. This two-way communication ensures a thorough and fair evaluation process.</p>
    </li>
    <hr class="my-5">
    <li>
        <p class="h5 font-weight-bold mb-2">Decision – Acceptance or Rejection</p>
        <p style="font-size: 16px;line-height: 1.7;">Each application is thoughtfully considered. If you're a match for our community, you'll be greeted with a warm welcome email and instructions to activate your account. If your application is not accepted, we will inform you respectfully, leaving the possibility open for future applications.</p>
    </li>
</ol>
<hr class="my-5">
<h5 class="text-center text-muted font-weight-light my-5">Why Curated Onboarding?</h5>
<ul>
    <li>
        <p class="h5 font-weight-bold mb-2">Fostering Quality Connections</p>
        <p style="font-size: 16px;line-height: 1.7;">At Pixelfed, we believe in the power of meaningful connections. It's not just about how many people are in the community, but how they enrich and enliven our platform. Our curated onboarding process is designed to welcome members who share our enthusiasm for creativity and engagement, ensuring every interaction on Pixelfed is enjoyable and rewarding.</p>
    </li>
    <hr class="my-5">
    <li>
        <p class="h5 font-weight-bold mb-2">Ensuring Safety and Respect</p>
        <p style="font-size: 16px;line-height: 1.7;">A careful onboarding process is critical for maintaining a safe and respectful environment. It allows Pixelfed to align every new member with the platform's values of kindness and inclusivity.</p>
    </li>
    <hr class="my-5">
    <li>
        <p class="h5 font-weight-bold mb-2">Encouraging Community Engagement</p>
        <p style="font-size: 16px;line-height: 1.7;">By engaging with applicants from the start, Pixelfed fosters a community that's not just active but passionate. This approach welcomes users who are genuinely interested in making a positive contribution to Pixelfed's vibrant community.</p>
    </li>
</ul>
<hr class="my-5">
<h5 class="text-center text-muted font-weight-light my-5">FAQs & Troubleshooting</h5>
<p>
    <a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse1" role="button" aria-expanded="false" aria-controls="collapse1">
        <i class="fas fa-chevron-down mr-2"></i>
        "You have re-attempted too many times."
    </a>
    <div class="collapse mb-5" id="collapse1">
        <div>
            This indicates that you've attempted to verify your email address too many times. This most likely is the result of an issue delivering the verification emails to your email provider. If you are experiencing this issue, we suggest that you <a href="/site/contact">contact the admin onboarding team</a> and mention that you're having issues verifying your email address.
        </div>
    </div>
</p>
<p>
    <a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse2" role="button" aria-expanded="false" aria-controls="collapse2">
        <i class="fas fa-chevron-down mr-2"></i>
        I haven't recieved the email confirmation
    </a>
    <div class="collapse mb-5" id="collapse2">
        <div>
            This indicates the desired username is already in-use or was previously used by a now deleted account. You need to pick a different username.
        </div>
    </div>
</p>
<p>
    <a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse3" role="button" aria-expanded="false" aria-controls="collapse3">
        <i class="fas fa-chevron-down mr-2"></i>
        "Email is invalid."
    </a>
    <div class="collapse mb-5" id="collapse3">
        <div>
            This indicates the desired email is not supported. While it may be a valid email, admins may have blocked specific domains from being associated with account email addresses.
        </div>
    </div>
</p>
<p>
    <a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse4" role="button" aria-expanded="false" aria-controls="collapse4">
        <i class="fas fa-chevron-down mr-2"></i>
        "The username has already been taken."
    </a>
    <div class="collapse mb-5" id="collapse4">
        <div>
            This indicates the desired username is already in-use or was previously used by a now deleted account. You need to pick a different username.
        </div>
    </div>
</p>
<p>
    <a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse5" role="button" aria-expanded="false" aria-controls="collapse5">
        <i class="fas fa-chevron-down mr-2"></i>
        "Username is invalid. Can only contain one dash (-), period (.) or underscore (_)."
    </a>
    <div class="collapse mb-5" id="collapse5">
        <div>
            This indicates the desired username is not a valid format, usernames may only contain one dash (-), period (.) or underscore (_) and must start with a letter or number.
        </div>
    </div>
</p>
<p>
    <a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse6" role="button" aria-expanded="false" aria-controls="collapse6">
        <i class="fas fa-chevron-down mr-2"></i>
        "The reason must be at least 20 characters."
    </a>
    <div class="collapse mb-5" id="collapse6">
        <div>
            This indicates the reason you provided for joining is less than the minimum accepted characters. The reason should be atleast 20 characters long, up to 1000 characters. If you desire to share a longer reason than 1000 characters, consider using a pastebin and posting the link. We can't guarantee that admins will visit any links you provided, so ideally you can keep the length within 1000 chars.
        </div>
    </div>
</p>
<p>
    <a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse7" role="button" aria-expanded="false" aria-controls="collapse7">
        <i class="fas fa-chevron-down mr-2"></i>
        My application was rejected, what can I do now?
    </a>
    <div class="collapse mb-5" id="collapse7">
        <div>
            <p>We understand that receiving a notification of rejection can be disappointing. Here's what you can consider if your application to join Pixelfed hasn't been successful:</p>

            <ul>
                <li>
                    <strong>Review Your Application:</strong> Reflect on the information you provided. Our decision may have been influenced by a variety of factors, including the clarity of your intentions or how well they align with our community values. Consider if there was any additional context or passion for Pixelfed that you could have included.
                </li>
                <li>
                    <strong>Reapply with Updated Information:</strong> We encourage you to reapply if you feel your initial application didn’t fully capture your enthusiasm or alignment with our community values. However, we recommend exercising caution and thoughtfulness. Please take time to refine and enhance your application before resubmitting, as repetitive or frequent submissions can overwhelm our admin team. We value careful consideration and meaningful updates in reapplications, as this helps us maintain a fair and manageable review process for everyone. Your patience and understanding in this regard are greatly appreciated and can positively influence the outcome of future applications.
                </li>
                <li>
                    <strong>Seek Feedback:</strong> If you are seeking clarity on why your application wasn't successful, you're welcome to contact us for feedback. However, please be mindful that our admins handle a high volume of queries and applications. While we strive to provide helpful responses, our ability to offer detailed individual feedback may be limited. We ask for your patience and understanding in this matter. When reaching out, ensure your query is concise and considerate of the admins' time. This approach will help us assist you more effectively and maintain a positive interaction, even if your initial application didn't meet our criteria.
                </li>
                <li>
                    <strong>Stay Engaged:</strong> Even if you're not a member yet, you can stay connected with Pixelfed through our public forums, blog, or social media channels. This will keep you updated on any changes or new features that might make our platform a better fit for you in the future.
                </li>
            </ul>

            <p>Remember, a rejection is not necessarily a reflection of your qualities or potential as a member of our community. It's often about finding the right fit at the right time. We appreciate your interest in Pixelfed and hope you won't be discouraged from exploring other ways to engage with our platform.</p>
            </p>
        </div>
    </div>
</p>
@endsection
