<x-galaxy-layout>
    <x-slot name="title">Privacy Policy</x-slot>

    <div class="container mx-auto px-6 py-8 max-w-4xl">
        <div class="relative p-8 glass-card">
            <div class="top-accent-center"></div>

            <h1 class="text-4xl font-bold text-white mb-4">Privacy Policy</h1>
            <p class="text-gray-400 mb-8">Last Updated: {{ date('F d, Y') }}</p>

            <div class="prose prose-invert max-w-none space-y-6 text-gray-300">
                <!-- Introduction -->
                <section>
                    <h2 class="text-2xl font-bold text-white mb-4">1. Introduction</h2>
                    <p>
                        At FunLynk, we take your privacy seriously. This Privacy Policy explains how we collect, use, and protect your personal information 
                        when you use our platform.
                    </p>
                    <p class="mt-4 p-4 bg-cyan-500/10 border border-cyan-500/30 rounded-lg">
                        <strong>Important:</strong> We do not sell your personal information to third parties. Your data is used solely to provide and improve our services.
                    </p>
                </section>

                <!-- Information We Collect -->
                <section>
                    <h2 class="text-2xl font-bold text-white mb-4">2. Information We Collect</h2>
                    
                    <h3 class="text-xl font-semibold text-white mb-3">2.1 Information You Provide</h3>
                    <ul class="list-disc pl-6 space-y-2">
                        <li><strong>Account Information:</strong> Username, email address, display name, profile photo</li>
                        <li><strong>Profile Information:</strong> Bio, interests, location (city/state)</li>
                        <li><strong>Content:</strong> Posts, events, comments, and messages you create</li>
                        <li><strong>Payment Information:</strong> Processed securely by Stripe (we do not store credit card details)</li>
                    </ul>

                    <h3 class="text-xl font-semibold text-white mb-3 mt-4">2.2 Information from Social Login</h3>
                    <p>
                        When you sign in with Google or Facebook, we receive:
                    </p>
                    <ul class="list-disc pl-6 space-y-2">
                        <li>Your name and email address</li>
                        <li>Your profile picture (if you choose to use it)</li>
                        <li>Basic profile information as permitted by the social platform</li>
                    </ul>
                    <p class="mt-4">
                        We only request the minimum information necessary to create and maintain your account. We do not access your social media posts, 
                        friends lists, or other private information.
                    </p>

                    <h3 class="text-xl font-semibold text-white mb-3 mt-4">2.3 Automatically Collected Information</h3>
                    <ul class="list-disc pl-6 space-y-2">
                        <li><strong>Usage Data:</strong> Pages visited, features used, time spent on platform</li>
                        <li><strong>Device Information:</strong> Browser type, operating system, IP address</li>
                        <li><strong>Location Data:</strong> Approximate location based on IP address (for showing nearby activities)</li>
                        <li><strong>Cookies:</strong> Small files stored on your device to maintain your session and preferences</li>
                    </ul>
                </section>

                <!-- How We Use Your Information -->
                <section>
                    <h2 class="text-2xl font-bold text-white mb-4">3. How We Use Your Information</h2>
                    <p>We use your information to:</p>
                    <ul class="list-disc pl-6 space-y-2">
                        <li>Provide and maintain our services</li>
                        <li>Create and manage your account</li>
                        <li>Show you relevant activities and events near you</li>
                        <li>Process payments and send transaction confirmations</li>
                        <li>Send important notifications about your account or events</li>
                        <li>Improve our platform and develop new features</li>
                        <li>Prevent fraud and ensure platform security</li>
                        <li>Comply with legal obligations</li>
                    </ul>
                </section>

                <!-- Information Sharing -->
                <section>
                    <h2 class="text-2xl font-bold text-white mb-4">4. Information Sharing</h2>
                    
                    <h3 class="text-xl font-semibold text-white mb-3">4.1 What We Share</h3>
                    <p>Your profile information (username, display name, bio, interests, location) is visible to other users on the platform.</p>
                    
                    <h3 class="text-xl font-semibold text-white mb-3 mt-4">4.2 Third-Party Services</h3>
                    <p>We share limited information with trusted service providers:</p>
                    <ul class="list-disc pl-6 space-y-2">
                        <li><strong>Stripe:</strong> Payment processing (they handle all payment information securely)</li>
                        <li><strong>AWS:</strong> Cloud hosting and file storage for profile images</li>
                        <li><strong>Google/Facebook:</strong> Authentication services (only when you choose social login)</li>
                    </ul>
                    <p class="mt-4">
                        These providers are contractually obligated to protect your data and may only use it to provide services to us.
                    </p>

                    <h3 class="text-xl font-semibold text-white mb-3 mt-4">4.3 What We Don't Share</h3>
                    <p class="p-4 bg-green-500/10 border border-green-500/30 rounded-lg">
                        <strong>We do not sell, rent, or trade your personal information to third parties for marketing purposes.</strong>
                    </p>
                </section>

                <!-- Data Security -->
                <section>
                    <h2 class="text-2xl font-bold text-white mb-4">5. Data Security</h2>
                    <p>We implement industry-standard security measures to protect your information:</p>
                    <ul class="list-disc pl-6 space-y-2">
                        <li>Encrypted data transmission (HTTPS/SSL)</li>
                        <li>Secure password hashing</li>
                        <li>Regular security audits and updates</li>
                        <li>Access controls and authentication</li>
                    </ul>
                    <p class="mt-4">
                        However, no method of transmission over the internet is 100% secure. While we strive to protect your data, 
                        we cannot guarantee absolute security.
                    </p>
                </section>

                <!-- Your Rights -->
                <section>
                    <h2 class="text-2xl font-bold text-white mb-4">6. Your Rights and Choices</h2>
                    <p>You have the right to:</p>
                    <ul class="list-disc pl-6 space-y-2">
                        <li><strong>Access:</strong> Request a copy of your personal data</li>
                        <li><strong>Correction:</strong> Update or correct your information through your profile settings</li>
                        <li><strong>Deletion:</strong> Delete your account and associated data at any time</li>
                        <li><strong>Opt-out:</strong> Unsubscribe from marketing emails (account notifications may still be sent)</li>
                        <li><strong>Data Portability:</strong> Request your data in a machine-readable format</li>
                    </ul>
                    <p class="mt-4">
                        To exercise these rights, contact us at privacy@funlynk.com or use the account settings in your profile.
                    </p>
                </section>

                <!-- Data Retention -->
                <section>
                    <h2 class="text-2xl font-bold text-white mb-4">7. Data Retention</h2>
                    <p>
                        We retain your personal information for as long as your account is active or as needed to provide services.
                        When you delete your account:
                    </p>
                    <ul class="list-disc pl-6 space-y-2">
                        <li>Your profile and personal information are permanently deleted</li>
                        <li>Your posts and events may be anonymized or removed</li>
                        <li>Transaction records are retained for legal and accounting purposes (typically 7 years)</li>
                        <li>Backup copies may persist for up to 90 days before permanent deletion</li>
                    </ul>
                </section>

                <!-- Children's Privacy -->
                <section>
                    <h2 class="text-2xl font-bold text-white mb-4">8. Children's Privacy</h2>
                    <p>
                        FunLynk is not intended for users under the age of 18. We do not knowingly collect information from children.
                        If we discover that a child has provided us with personal information, we will delete it immediately.
                    </p>
                </section>

                <!-- Cookies -->
                <section>
                    <h2 class="text-2xl font-bold text-white mb-4">9. Cookies and Tracking</h2>
                    <p>We use cookies and similar technologies to:</p>
                    <ul class="list-disc pl-6 space-y-2">
                        <li>Keep you logged in</li>
                        <li>Remember your preferences</li>
                        <li>Analyze platform usage and performance</li>
                        <li>Provide personalized content</li>
                    </ul>
                    <p class="mt-4">
                        You can control cookies through your browser settings, but disabling them may affect platform functionality.
                    </p>
                </section>

                <!-- International Users -->
                <section>
                    <h2 class="text-2xl font-bold text-white mb-4">10. International Users</h2>
                    <p>
                        FunLynk is based in the United States. If you access our platform from outside the U.S., your information may be
                        transferred to and processed in the United States. By using FunLynk, you consent to this transfer.
                    </p>
                </section>

                <!-- Changes to Privacy Policy -->
                <section>
                    <h2 class="text-2xl font-bold text-white mb-4">11. Changes to This Privacy Policy</h2>
                    <p>
                        We may update this Privacy Policy from time to time. We will notify you of significant changes via email or platform notification.
                        The "Last Updated" date at the top of this page indicates when the policy was last revised.
                    </p>
                </section>

                <!-- California Privacy Rights -->
                <section>
                    <h2 class="text-2xl font-bold text-white mb-4">12. California Privacy Rights (CCPA)</h2>
                    <p>
                        If you are a California resident, you have additional rights under the California Consumer Privacy Act (CCPA):
                    </p>
                    <ul class="list-disc pl-6 space-y-2">
                        <li>Right to know what personal information we collect and how it's used</li>
                        <li>Right to delete your personal information</li>
                        <li>Right to opt-out of the sale of personal information (we don't sell your data)</li>
                        <li>Right to non-discrimination for exercising your privacy rights</li>
                    </ul>
                    <p class="mt-4">
                        To exercise these rights, contact us at privacy@funlynk.com with "CCPA Request" in the subject line.
                    </p>
                </section>

                <!-- Contact -->
                <section>
                    <h2 class="text-2xl font-bold text-white mb-4">13. Contact Us</h2>
                    <p>
                        If you have questions or concerns about this Privacy Policy or our data practices, please contact us:
                    </p>
                    <p class="mt-4 p-4 bg-slate-800/50 border border-white/10 rounded-lg">
                        <strong>Email:</strong> privacy@funlynk.com<br>
                        <strong>Support:</strong> support@funlynk.com<br>
                        <strong>Website:</strong> <a href="{{ route('home') }}" class="text-cyan-400 hover:text-cyan-300">funlynk.com</a>
                    </p>
                </section>

                <!-- Summary -->
                <section class="mt-8 p-6 bg-purple-500/10 border border-purple-500/30 rounded-xl">
                    <h2 class="text-2xl font-bold text-white mb-4">Summary</h2>
                    <p class="font-semibold mb-3">In plain English:</p>
                    <ul class="list-disc pl-6 space-y-2">
                        <li>We collect information you provide and some automatic data to make the platform work</li>
                        <li>We use your data to provide services, not to sell to advertisers</li>
                        <li>Your profile info is visible to other users (that's how social platforms work)</li>
                        <li>We use trusted partners like Stripe for payments and AWS for hosting</li>
                        <li>You can delete your account and data anytime</li>
                        <li>We take security seriously and follow industry best practices</li>
                        <li>We'll notify you if we make major changes to this policy</li>
                    </ul>
                </section>
            </div>
        </div>
    </div>
</x-galaxy-layout>


