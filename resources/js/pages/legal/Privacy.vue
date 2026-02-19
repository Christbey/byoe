<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import LegalLayout from '@/layouts/LegalLayout.vue';

defineProps<{
    company: string;
    email: string;
    state: string;
    effectiveDate: string;
}>();
</script>

<template>
    <Head title="Privacy Policy" />

    <LegalLayout title="Privacy Policy" :effective-date="effectiveDate">
        <p>
            {{ company }} ("we", "us", or "our") is committed to protecting your personal information. This
            Privacy Policy explains what we collect, how we use it, and your rights regarding your data when
            you use the BYOE platform.
        </p>

        <h2>1. Information We Collect</h2>

        <h3>Information You Provide</h3>
        <ul>
            <li><strong>Account information:</strong> name, email address, and password when you register.</li>
            <li><strong>Profile information:</strong> business name, location, skills, years of experience, certifications, availability schedule, and preferred service area (for Providers); shop name, address, and operating details (for Shops).</li>
            <li><strong>Payment information:</strong> billing details collected and stored securely by our payment processor, Stripe. We do not store full card numbers or bank account details on our servers.</li>
            <li><strong>Communications:</strong> messages, support requests, and any other content you submit to us.</li>
        </ul>

        <h3>Information Collected Automatically</h3>
        <ul>
            <li><strong>Usage data:</strong> pages visited, features used, search queries, and interaction timestamps.</li>
            <li><strong>Device data:</strong> IP address, browser type, operating system, and device identifiers.</li>
            <li><strong>Location data:</strong> approximate location derived from IP address; precise GPS coordinates if you grant permission through the "Nearby" filter feature (used only to display relevant service requests and never stored persistently).</li>
            <li><strong>Cookies and similar technologies:</strong> see our <a href="/legal/cookies">Cookie Policy</a> for details.</li>
        </ul>

        <h2>2. How We Use Your Information</h2>
        <p>We use the information we collect to:</p>
        <ul>
            <li>Create and manage your account and provide platform features;</li>
            <li>Match Providers with available service requests based on location, skills, and availability;</li>
            <li>Process bookings and facilitate payments between Shops and Providers;</li>
            <li>Send transactional emails (booking confirmations, payout notifications, password resets);</li>
            <li>Display ratings and reviews;</li>
            <li>Detect fraud, enforce our Terms of Service, and comply with legal obligations;</li>
            <li>Improve platform performance through aggregated, anonymized analytics.</li>
        </ul>
        <p>
            We do not sell your personal information to third parties. We do not use your data for
            behavioral advertising.
        </p>

        <h2>3. How We Share Your Information</h2>

        <h3>Between Users</h3>
        <p>
            When a booking is made, relevant details (name, service date, location, and contact information)
            are shared between the Shop and the Provider involved in that booking. Provider profile
            information (skills, ratings, bio) is visible to Shops browsing the platform.
        </p>

        <h3>Service Providers</h3>
        <p>We work with the following categories of third-party service providers:</p>
        <ul>
            <li><strong>Stripe</strong> — payment processing and identity verification for Providers;</li>
            <li><strong>Email service providers</strong> — transactional and notification emails;</li>
            <li><strong>Hosting and infrastructure</strong> — secure cloud hosting of the platform.</li>
        </ul>
        <p>
            All third-party providers are contractually required to protect your information and use it only
            for the purposes we specify.
        </p>

        <h3>Legal Requirements</h3>
        <p>
            We may disclose your information if required by law, court order, or governmental authority, or
            to protect the rights, safety, and property of {{ company }}, our users, or the public.
        </p>

        <h2>4. Data Retention</h2>
        <p>
            We retain your account information for as long as your account is active. After account closure,
            we retain data for up to three years to comply with tax, legal, and dispute-resolution
            obligations. Booking records, including payment histories and 1099-related data, are retained
            for seven years in accordance with IRS recordkeeping requirements.
        </p>
        <p>
            Geocoding results (zip code to coordinate lookups) are cached for 30 days and then discarded.
            GPS coordinates used for the "Nearby" filter are never stored on our servers.
        </p>

        <h2>5. Security</h2>
        <p>
            We implement industry-standard security measures including encryption in transit (TLS), encrypted
            storage of sensitive fields, and access controls. No method of transmission over the internet is
            100% secure. If you believe your account has been compromised, contact us immediately at
            <a :href="`mailto:${email}`">{{ email }}</a>.
        </p>

        <h2>6. Your Rights</h2>
        <p>Depending on your location, you may have the right to:</p>
        <ul>
            <li>Access a copy of the personal information we hold about you;</li>
            <li>Correct inaccurate information;</li>
            <li>Request deletion of your account and associated data (subject to retention obligations above);</li>
            <li>Object to or restrict certain processing;</li>
            <li>Withdraw consent where processing is based on consent.</li>
        </ul>
        <p>
            To exercise any of these rights, email us at <a :href="`mailto:${email}`">{{ email }}</a>. We
            will respond within 30 days.
        </p>

        <h2>7. Children's Privacy</h2>
        <p>
            The Platform is not directed to individuals under 18. We do not knowingly collect personal
            information from minors. If we learn that we have collected data from a minor, we will delete it
            promptly.
        </p>

        <h2>8. Changes to This Policy</h2>
        <p>
            We may update this Privacy Policy from time to time. Material changes will be communicated via
            email or a platform notice at least 14 days before taking effect. Continued use of the Platform
            after that date constitutes acceptance of the updated policy.
        </p>

        <h2>9. Contact</h2>
        <p>
            Questions or concerns about this Privacy Policy? Contact our privacy team at
            <a :href="`mailto:${email}`">{{ email }}</a>.
        </p>
    </LegalLayout>
</template>
