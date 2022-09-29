@extends('layouts.blank')

@section('content')
<div class="container mt-5">
    <div class="col-12 px-0">
        <h3 class="font-weight-bold">Terms of Use</h3>
        <p class="text-muted small">Last Updated: Sept 28, 2022</p>
        <div class="card shadow-none">
            <div class="card-body text-justify">
                @if($page && $page->content)
                {!! $page->content !!}
                @else
                <div class="terms">
                    <h5 class="font-weight-bold">1. Terms</h5>
                    <p class="">By accessing the website at <a href="{{config('app.url')}}">{{config('app.url')}}</a>, you are agreeing to be bound by these terms of service, all applicable laws and regulations, and agree that you are responsible for compliance with any applicable local laws. If you do not agree with any of these terms, you are prohibited from using or accessing this site. The materials contained in this website are protected by applicable copyright and trademark law.</p>
                    <h5 class="font-weight-bold mt-5">2. Use License</h5>
                    <ol class="" type="a">
                       <li>Permission is granted to temporarily download one copy of the materials (information or software) on Pixelfed's website for personal, non-commercial transitory viewing only. This is the grant of a license, not a transfer of title, and under this license you may not:
                       <ol class="" type="i">
                           <li>modify or copy the materials;</li>
                           <li>use the materials for any commercial purpose, or for any public display (commercial or non-commercial);</li>
                           <li>attempt to decompile or reverse engineer any software contained on Pixelfed's website;</li>
                           <li>remove any copyright or other proprietary notations from the materials; or</li>
                           <li>transfer the materials to another person or "mirror" the materials on any other server.</li>
                       </ol>
                        </li>
                       <li>This license shall automatically terminate if you violate any of these restrictions and may be terminated by Pixelfed at any time. Upon terminating your viewing of these materials or upon the termination of this license, you must destroy any downloaded materials in your possession whether in electronic or printed format.</li>
                    </ol>
                    <h5 class="font-weight-bold mt-5">3. Disclaimer</h5>
                    <ol class="" type="a">
                       <li>The materials on Pixelfed's website are provided on an 'as is' basis. Pixelfed makes no warranties, expressed or implied, and hereby disclaims and negates all other warranties including, without limitation, implied warranties or conditions of merchantability, fitness for a particular purpose, or non-infringement of intellectual property or other violation of rights.</li>
                       <li>Further, Pixelfed does not warrant or make any representations concerning the accuracy, likely results, or reliability of the use of the materials on its website or otherwise relating to such materials or on any sites linked to this site.</li>
                    </ol>
                    <h5 class="font-weight-bold mt-5">4. Limitations</h5>
                    <p class="">In no event shall Pixelfed or its suppliers be liable for any damages (including, without limitation, damages for loss of data or profit, or due to business interruption) arising out of the use or inability to use the materials on Pixelfed's website, even if Pixelfed or a Pixelfed authorized representative has been notified orally or in writing of the possibility of such damage. Because some jurisdictions do not allow limitations on implied warranties, or limitations of liability for consequential or incidental damages, these limitations may not apply to you.</p>
                    <h5 class="font-weight-bold mt-5">5. Accuracy of materials</h5>
                    <p class="">The materials appearing on Pixelfed's website could include technical, typographical, or photographic errors. Pixelfed does not warrant that any of the materials on its website are accurate, complete or current. Pixelfed may make changes to the materials contained on its website at any time without notice. However Pixelfed does not make any commitment to update the materials.</p>
                    <h5 class="font-weight-bold mt-5">6. Links</h5>
                    <p class="">Pixelfed has not reviewed all of the sites linked to its website and is not responsible for the contents of any such linked site. The inclusion of any link does not imply endorsement by Pixelfed of the site. Use of any such linked website is at the user's own risk.</p>
                    <h5 class="font-weight-bold mt-5">7. Modifications</h5>
                    <p class="">Pixelfed may revise these terms of service for its website at any time without notice. By using this website you are agreeing to be bound by the then current version of these terms of service.</p>
                    <h5 class="font-weight-bold mt-5">8. Governing Law</h5>
                    <p class="">These terms and conditions are governed by and construed in accordance with the laws of Canada and you irrevocably submit to the exclusive jurisdiction of the courts in that State or location.</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('meta')
<meta property="og:description" content="Terms of Use">
@endpush

@push('styles')
<style type="text/css">
    body {
        background-color: #fff;
    }
</style>
@endpush
