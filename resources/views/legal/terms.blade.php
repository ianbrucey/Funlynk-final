<x-galaxy-layout>
    <x-slot name="title">Terms of Service</x-slot>

    <div class="container mx-auto px-6 py-8 max-w-4xl">
        <div class="relative p-8 glass-card">
            <div class="top-accent-center"></div>

            <h1 class="text-4xl font-bold text-white mb-4">Terms of Service</h1>
            <p class="text-gray-400 mb-8">Last Updated: {{ date('F d, Y') }}</p>

            <div class="prose prose-invert max-w-none space-y-6 text-gray-300">
                <!-- Introduction -->
                <section>
                    <h2 class="text-2xl font-bold text-white mb-4">1. Acceptance of Terms</h2>
                    <p>
                        Welcome to FunLynk ("we," "our," or "us"). By accessing or using our platform, you agree to be bound by these Terms of Service ("Terms"). 
                        If you do not agree to these Terms, please do not use our services.
                    </p>
                </section>

                <!-- Service Description -->
                <section>
                    <h2 class="text-2xl font-bold text-white mb-4">2. Service Description</h2>
                    <p>
                        FunLynk is a social activity discovery platform that allows users to:
                    </p>
                    <ul class="list-disc pl-6 space-y-2">
                        <li>Create and discover spontaneous activity posts</li>
                        <li>Organize and attend structured events</li>
                        <li>Connect with other users who share similar interests</li>
                        <li>Purchase tickets for paid events</li>
                    </ul>
                </section>

                <!-- User Responsibilities -->
                <section>
                    <h2 class="text-2xl font-bold text-white mb-4">3. User Responsibilities</h2>
                    <p>As a user of FunLynk, you agree to:</p>
                    <ul class="list-disc pl-6 space-y-2">
                        <li>Provide accurate and truthful information</li>
                        <li>Maintain the security of your account credentials</li>
                        <li>Comply with all applicable laws and regulations</li>
                        <li>Respect other users and their property</li>
                        <li>Not engage in fraudulent, abusive, or illegal activities</li>
                    </ul>
                </section>

                <!-- Liability Disclaimer -->
                <section>
                    <h2 class="text-2xl font-bold text-white mb-4">4. Limitation of Liability</h2>
                    <p class="font-semibold text-yellow-400 mb-3">IMPORTANT: Please read this section carefully.</p>
                    <p>
                        FunLynk is a platform that facilitates connections between users. We are not responsible for:
                    </p>
                    <ul class="list-disc pl-6 space-y-2">
                        <li><strong>In-person meetings:</strong> Any interactions, meetings, or activities that occur between users in the physical world</li>
                        <li><strong>User conduct:</strong> The behavior, actions, or statements of any user on or off the platform</li>
                        <li><strong>Event quality:</strong> The quality, safety, or legality of any events organized through our platform</li>
                        <li><strong>Personal safety:</strong> Your personal safety when attending events or meeting other users</li>
                        <li><strong>Property damage:</strong> Any loss, damage, or injury to persons or property</li>
                        <li><strong>Third-party services:</strong> Services provided by event hosts or other third parties</li>
                    </ul>
                    <p class="mt-4 p-4 bg-yellow-500/10 border border-yellow-500/30 rounded-lg">
                        <strong>You acknowledge and agree that you use FunLynk at your own risk.</strong> We strongly encourage you to exercise caution, 
                        use good judgment, and take appropriate safety precautions when meeting people or attending events.
                    </p>
                </section>

                <!-- Payment Terms -->
                <section>
                    <h2 class="text-2xl font-bold text-white mb-4">5. Payment and Refund Policy</h2>
                    
                    <h3 class="text-xl font-semibold text-white mb-3">5.1 Payment Processing</h3>
                    <p>
                        All payments are processed securely through Stripe. By making a purchase, you agree to Stripe's terms of service. 
                        FunLynk does not store your payment information.
                    </p>

                    <h3 class="text-xl font-semibold text-white mb-3 mt-4">5.2 Event Host Responsibilities</h3>
                    <p>Event hosts who charge for tickets agree to:</p>
                    <ul class="list-disc pl-6 space-y-2">
                        <li>Deliver the event as described</li>
                        <li>Provide accurate event information</li>
                        <li>Honor confirmed RSVPs and ticket purchases</li>
                        <li>Comply with all applicable laws and regulations</li>
                    </ul>

                    <h3 class="text-xl font-semibold text-white mb-3 mt-4">5.3 Refund Policy</h3>
                    <p>Refunds are handled on a case-by-case basis:</p>
                    <ul class="list-disc pl-6 space-y-2">
                        <li><strong>Event cancellation by host:</strong> Full refund automatically issued</li>
                        <li><strong>Event changes:</strong> Attendees may request a refund if significant changes are made to the event</li>
                        <li><strong>Attendee cancellation:</strong> Refunds are at the discretion of the event host and their cancellation policy</li>
                        <li><strong>Disputes:</strong> Contact support at support@funlynk.com within 48 hours of the issue</li>
                    </ul>

                    <h3 class="text-xl font-semibold text-white mb-3 mt-4">5.4 Platform Fees</h3>
                    <p>
                        FunLynk charges a service fee on paid events. This fee covers payment processing, platform maintenance, and customer support.
                        Fees are clearly displayed before purchase and are non-refundable.
                    </p>
                </section>

                <!-- Dispute Resolution -->
                <section>
                    <h2 class="text-2xl font-bold text-white mb-4">6. Dispute Resolution</h2>
                    <p>
                        If you have a dispute with another user or event host:
                    </p>
                    <ul class="list-disc pl-6 space-y-2">
                        <li>First, attempt to resolve the issue directly with the other party</li>
                        <li>If unresolved, contact our support team at support@funlynk.com</li>
                        <li>Provide detailed information including event details, transaction IDs, and communication records</li>
                        <li>Our team will review the case and may mediate, but final decisions rest with the parties involved</li>
                    </ul>
                    <p class="mt-4">
                        FunLynk reserves the right to suspend or terminate accounts that violate these Terms or engage in fraudulent behavior.
                    </p>
                </section>

                <!-- Prohibited Activities -->
                <section>
                    <h2 class="text-2xl font-bold text-white mb-4">7. Prohibited Activities</h2>
                    <p>You may not use FunLynk to:</p>
                    <ul class="list-disc pl-6 space-y-2">
                        <li>Violate any laws or regulations</li>
                        <li>Harass, threaten, or harm other users</li>
                        <li>Post false, misleading, or fraudulent content</li>
                        <li>Sell illegal goods or services</li>
                        <li>Infringe on intellectual property rights</li>
                        <li>Spam or send unsolicited messages</li>
                        <li>Attempt to gain unauthorized access to our systems</li>
                    </ul>
                </section>

                <!-- Intellectual Property -->
                <section>
                    <h2 class="text-2xl font-bold text-white mb-4">8. Intellectual Property</h2>
                    <p>
                        All content on FunLynk, including text, graphics, logos, and software, is the property of FunLynk or its licensors.
                        You may not copy, modify, or distribute our content without permission.
                    </p>
                    <p class="mt-4">
                        By posting content on FunLynk, you grant us a non-exclusive, worldwide license to use, display, and distribute your content
                        on our platform.
                    </p>
                </section>

                <!-- Account Termination -->
                <section>
                    <h2 class="text-2xl font-bold text-white mb-4">9. Account Termination</h2>
                    <p>
                        We reserve the right to suspend or terminate your account at any time for violations of these Terms, fraudulent activity,
                        or any other reason at our discretion. You may also delete your account at any time through your profile settings.
                    </p>
                </section>

                <!-- Changes to Terms -->
                <section>
                    <h2 class="text-2xl font-bold text-white mb-4">10. Changes to Terms</h2>
                    <p>
                        We may update these Terms from time to time. We will notify you of significant changes via email or platform notification.
                        Your continued use of FunLynk after changes constitutes acceptance of the new Terms.
                    </p>
                </section>

                <!-- Contact -->
                <section>
                    <h2 class="text-2xl font-bold text-white mb-4">11. Contact Us</h2>
                    <p>
                        If you have questions about these Terms, please contact us at:
                    </p>
                    <p class="mt-4 p-4 bg-slate-800/50 border border-white/10 rounded-lg">
                        <strong>Email:</strong> support@funlynk.com<br>
                        <strong>Website:</strong> <a href="{{ route('home') }}" class="text-cyan-400 hover:text-cyan-300">funlynk.com</a>
                    </p>
                </section>

                <!-- Governing Law -->
                <section>
                    <h2 class="text-2xl font-bold text-white mb-4">12. Governing Law</h2>
                    <p>
                        These Terms are governed by the laws of the United States. Any disputes will be resolved in the courts of [Your State/Jurisdiction].
                    </p>
                </section>
            </div>
        </div>
    </div>
</x-galaxy-layout>


