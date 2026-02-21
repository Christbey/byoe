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
    <Head title="Terms of Service" />

    <LegalLayout title="Terms of Service" :effective-date="effectiveDate">
        <h2>1. Acceptance of Terms</h2>
        <p>
            By accessing or using the ShiftFinder platform ("Platform"), you agree to be bound by these Terms of
            Service ("Terms"). If you do not agree, you may not use the Platform. These Terms apply to all
            users, including coffee shop owners, managers, and independent service providers.
        </p>

        <h2>2. About ShiftFinder</h2>
        <p>
            ShiftFinder is an online marketplace that connects coffee shops and similar food-and-beverage businesses
            ("Shops") with independent skilled service providers ("Providers") for on-demand shift coverage
            and specialty services. {{ company }} operates the Platform and facilitates the posting,
            discovery, booking, and payment of service engagements — but is not a party to the service
            agreement between a Shop and a Provider.
        </p>

        <h2>3. Eligibility</h2>
        <p>
            You must be at least 18 years old and legally permitted to enter contracts in your jurisdiction
            to use the Platform. By registering, you represent and warrant that all information you provide
            is accurate, current, and complete.
        </p>

        <h2>4. Accounts</h2>
        <p>
            You are responsible for maintaining the confidentiality of your account credentials and for all
            activity that occurs under your account. Notify us immediately at
            <a :href="`mailto:${email}`">{{ email }}</a> if you suspect unauthorized access.
            {{ company }} reserves the right to suspend or terminate accounts that violate these Terms.
        </p>

        <h2>5. Service Requests</h2>
        <p>
            Shops may post service requests describing the shift date, time, location, required skills, and
            fixed compensation. Requests remain open until a Provider accepts them or the listing expires.
            Shops are responsible for the accuracy of request details. Providers accept requests on an
            at-will basis with no obligation to accept any particular request.
        </p>

        <h2>6. Independent Contractor Relationship</h2>
        <p>
            Providers who accept service requests perform work as independent contractors, not employees,
            agents, or partners of {{ company }} or the Shop. {{ company }} does not control the manner or
            means by which Providers perform their services. Each Provider is solely responsible for their
            own taxes, insurance, equipment, and compliance with applicable laws. See the
            <a href="/legal/contractor">Independent Contractor Agreement</a> for full details.
        </p>

        <h2>7. Payments and Fees</h2>
        <p>
            All payments are processed through our third-party payment processor (Stripe). Shops authorize
            payment at the time a service request is funded. Upon booking completion, {{ company }} releases
            the provider payout minus any applicable platform fee. {{ company }} reserves the right to
            adjust its fee structure with reasonable notice.
        </p>
        <p>
            {{ company }} is not responsible for disputes between Shops and Providers regarding the quality
            or completion of services. Refund or dispute requests must be submitted through the Platform
            within 48 hours of the scheduled service date.
        </p>

        <h2>8. Ratings and Reviews</h2>
        <p>
            Both Shops and Providers may rate each other following a completed booking. Ratings must be
            honest and reflect the actual service experience. {{ company }} reserves the right to remove
            ratings that violate these Terms or that appear fraudulent.
        </p>

        <h2>9. Prohibited Conduct</h2>
        <p>You agree not to:</p>
        <ul>
            <li>Use the Platform for any unlawful purpose or in violation of applicable regulations;</li>
            <li>Circumvent the Platform to arrange or pay for services outside of ShiftFinder;</li>
            <li>Post false, misleading, or fraudulent information;</li>
            <li>Harass, threaten, or discriminate against other users;</li>
            <li>Attempt to gain unauthorized access to any part of the Platform or its systems;</li>
            <li>Scrape, copy, or redistribute Platform content without written permission.</li>
        </ul>
        <p>
            Violation of these prohibitions may result in immediate account suspension or termination without
            refund.
        </p>

        <h2>10. Intellectual Property</h2>
        <p>
            The Platform and all associated content, trademarks, and software are owned by {{ company }}
            or its licensors. You are granted a limited, non-exclusive, non-transferable license to access
            the Platform solely for its intended purpose. You may not reproduce or distribute any part of
            the Platform without prior written consent.
        </p>

        <h2>11. Disclaimers</h2>
        <p>
            THE PLATFORM IS PROVIDED "AS IS" AND "AS AVAILABLE" WITHOUT WARRANTIES OF ANY KIND, EXPRESS
            OR IMPLIED. {{ company }} DOES NOT WARRANT THAT THE PLATFORM WILL BE ERROR-FREE, UNINTERRUPTED,
            OR FREE OF HARMFUL COMPONENTS. {{ company }} MAKES NO REPRESENTATIONS REGARDING THE QUALITY,
            RELIABILITY, OR SUITABILITY OF ANY PROVIDER OR SHOP.
        </p>

        <h2>12. Limitation of Liability</h2>
        <p>
            TO THE MAXIMUM EXTENT PERMITTED BY LAW, {{ company }} AND ITS OFFICERS, DIRECTORS, EMPLOYEES,
            AND AGENTS SHALL NOT BE LIABLE FOR ANY INDIRECT, INCIDENTAL, SPECIAL, CONSEQUENTIAL, OR PUNITIVE
            DAMAGES ARISING FROM YOUR USE OF THE PLATFORM OR ANY SERVICE ARRANGED THROUGH IT. OUR TOTAL
            LIABILITY TO YOU FOR ANY CLAIM SHALL NOT EXCEED THE FEES PAID BY YOU TO {{ company }} IN THE
            THREE MONTHS PRECEDING THE CLAIM.
        </p>

        <h2>13. Indemnification</h2>
        <p>
            You agree to indemnify and hold harmless {{ company }} and its affiliates from any claims,
            damages, or expenses (including reasonable attorneys' fees) arising out of your use of the
            Platform, your violation of these Terms, or your violation of any third-party rights.
        </p>

        <h2>14. Termination</h2>
        <p>
            Either party may terminate these Terms at any time. {{ company }} may suspend or terminate your
            access immediately for any violation of these Terms. Upon termination, all licenses granted to
            you cease immediately, and any outstanding obligations (including payment) remain enforceable.
        </p>

        <h2>15. Governing Law and Dispute Resolution</h2>
        <p>
            These Terms are governed by the laws of the State of {{ state }}, without regard to conflict-of-law
            principles. Any dispute arising under these Terms shall be resolved through binding arbitration
            administered under the American Arbitration Association's Consumer Arbitration Rules, conducted
            in {{ state }}. You waive the right to participate in class-action proceedings.
        </p>

        <h2>16. Changes to These Terms</h2>
        <p>
            {{ company }} may update these Terms from time to time. We will notify you of material changes
            via email or a prominent notice on the Platform. Continued use of the Platform after the
            effective date of updated Terms constitutes your acceptance.
        </p>

        <h2>17. Contact</h2>
        <p>
            Questions about these Terms? Contact us at
            <a :href="`mailto:${email}`">{{ email }}</a>.
        </p>
    </LegalLayout>
</template>
