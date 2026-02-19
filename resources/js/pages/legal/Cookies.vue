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
    <Head title="Cookie Policy" />

    <LegalLayout title="Cookie Policy" :effective-date="effectiveDate">
        <p>
            This Cookie Policy explains how {{ company }} uses cookies and similar technologies on the BYOE
            platform. By continuing to use the Platform, you consent to our use of cookies as described
            below.
        </p>

        <h2>1. What Are Cookies?</h2>
        <p>
            Cookies are small text files that a website stores on your device when you visit. They help the
            site remember your preferences, keep you logged in, and understand how you use it. Similar
            technologies include local storage, session storage, and pixels.
        </p>

        <h2>2. Cookies We Use</h2>

        <h3>Strictly Necessary Cookies</h3>
        <p>
            These cookies are required for the Platform to function. You cannot opt out of them without
            disabling the Platform entirely.
        </p>
        <ul>
            <li>
                <strong>Session cookie</strong> — keeps you logged in between page loads and stores
                temporary state (such as flash messages). Expires when you close your browser or after
                your session timeout.
            </li>
            <li>
                <strong>CSRF token</strong> — a security token included with every form submission to
                prevent cross-site request forgery attacks. Session-scoped.
            </li>
            <li>
                <strong>XSRF-TOKEN</strong> — a cookie-based CSRF token used by our frontend JavaScript
                to authenticate API requests. Session-scoped.
            </li>
        </ul>

        <h3>Functional Cookies</h3>
        <p>
            These cookies remember your preferences to improve your experience.
        </p>
        <ul>
            <li>
                <strong>Appearance preference</strong> — stores whether you have selected light or dark
                mode. Persists for 1 year.
            </li>
            <li>
                <strong>Dismissed UI hints</strong> — remembers if you have dismissed help prompts or
                onboarding guides so they are not shown repeatedly. Persists for 90 days.
            </li>
        </ul>

        <h3>Analytics Cookies</h3>
        <p>
            If we use analytics tools in the future, we will update this policy and obtain appropriate
            consent before setting any analytics cookies. Currently, the Platform does not use third-party
            analytics cookies.
        </p>

        <h3>Payment Processor Cookies</h3>
        <p>
            When you interact with payment features, Stripe may set cookies on our pages for fraud
            detection and security purposes. These are governed by
            <a href="https://stripe.com/privacy" target="_blank" rel="noopener noreferrer">Stripe's Privacy Policy</a>.
        </p>

        <h2>3. Local Storage</h2>
        <p>
            In addition to cookies, the Platform may use browser local storage to cache non-sensitive
            UI preferences (such as sidebar state or page help dismissal). This data never leaves your
            device and is not transmitted to our servers.
        </p>

        <h2>4. How to Control Cookies</h2>
        <p>
            You can control cookies through your browser settings. Most browsers allow you to view,
            delete, and block cookies. Note that blocking strictly necessary cookies will prevent you from
            logging in and using the Platform.
        </p>
        <p>Common browser cookie settings:</p>
        <ul>
            <li><strong>Chrome:</strong> Settings → Privacy and security → Cookies and other site data</li>
            <li><strong>Firefox:</strong> Settings → Privacy &amp; Security → Cookies and Site Data</li>
            <li><strong>Safari:</strong> Preferences → Privacy → Manage Website Data</li>
            <li><strong>Edge:</strong> Settings → Cookies and site permissions → Cookies and site data</li>
        </ul>

        <h2>5. Do Not Track</h2>
        <p>
            Some browsers send a "Do Not Track" signal. Because the Platform does not use behavioral
            advertising or cross-site tracking cookies, the practical effect of this signal on your
            experience is minimal. We honor it by not setting any non-essential tracking cookies.
        </p>

        <h2>6. Changes to This Policy</h2>
        <p>
            We may update this Cookie Policy as we introduce new features or third-party integrations.
            Material changes will be noted on the Platform and the effective date above will be updated.
        </p>

        <h2>7. Contact</h2>
        <p>
            Questions about our cookie practices? Email us at
            <a :href="`mailto:${email}`">{{ email }}</a>.
        </p>
    </LegalLayout>
</template>
