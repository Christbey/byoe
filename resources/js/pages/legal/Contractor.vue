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
    <Head title="Independent Contractor Agreement" />

    <LegalLayout title="Independent Contractor Agreement" :effective-date="effectiveDate">
        <p>
            This Independent Contractor Agreement ("Agreement") governs the relationship between
            {{ company }} ("Company") and any individual who registers as a Provider on the ShiftFinder platform
            ("Contractor"). By creating a Provider account and accepting service requests, you agree to the
            terms of this Agreement.
        </p>

        <h2>1. Nature of Relationship</h2>
        <p>
            The Contractor is an independent contractor and not an employee, agent, joint venture partner,
            or franchisee of {{ company }} or of any Shop. Nothing in this Agreement or on the Platform
            creates an employment relationship. The Contractor retains full control over the manner and
            means of performing any accepted service, consistent with the requirements described in the
            relevant service request.
        </p>
        <p>
            {{ company }} provides a technology platform that facilitates connections between Shops and
            Contractors. {{ company }} does not direct, supervise, or control the Contractor's work. Shops
            set the time, location, and nature of the shift, but the Contractor decides how to deliver the
            service within those parameters.
        </p>

        <h2>2. Services</h2>
        <p>
            The Contractor may browse available service requests posted by Shops and accept those for which
            they are qualified. Upon accepting a request, the Contractor agrees to appear at the designated
            location at the scheduled time and perform the services described in the request to a
            professional standard.
        </p>
        <p>
            The Contractor may decline any service request for any reason and may work for other clients
            or platforms without restriction, provided there is no conflict with an accepted booking.
        </p>

        <h2>3. Compensation</h2>
        <p>
            Compensation for each engagement is set by the Shop in the service request and is fixed at the
            time of booking. {{ company }} remits payment to the Contractor via Stripe after the booking is
            marked complete, minus any applicable platform fee. Payment timelines are subject to Stripe's
            standard processing schedule and the Contractor's connected account status.
        </p>
        <p>
            {{ company }} may adjust its platform fee with at least 14 days' written notice. Fee changes do
            not apply retroactively to already-accepted bookings.
        </p>

        <h2>4. Taxes</h2>
        <p>
            The Contractor is solely responsible for reporting and paying all applicable federal, state,
            and local taxes on compensation received through the Platform, including self-employment tax.
            {{ company }} will issue a Form 1099-NEC to Contractors who receive $600 or more in a calendar
            year, as required by the IRS. The Contractor must provide accurate tax identification
            information through the Platform; failure to do so may result in backup withholding.
        </p>
        <p>
            {{ company }} does not withhold income taxes, Social Security, Medicare, or unemployment
            insurance contributions on behalf of the Contractor.
        </p>

        <h2>5. Benefits</h2>
        <p>
            As an independent contractor, you are not entitled to any employee benefits provided by
            {{ company }}, including but not limited to health insurance, paid leave, retirement plans,
            workers' compensation coverage, or unemployment insurance. You are responsible for obtaining
            your own insurance coverage.
        </p>

        <h2>6. Equipment and Expenses</h2>
        <p>
            Unless otherwise specified in the service request, the Contractor is responsible for providing
            their own tools, equipment, and supplies necessary to perform the services. Any business
            expenses incurred are the sole responsibility of the Contractor and are not reimbursed by
            {{ company }}.
        </p>

        <h2>7. Licenses and Compliance</h2>
        <p>
            The Contractor is responsible for maintaining any licenses, certifications, or permits required
            by applicable law to perform their services (e.g., food handler's permits). The Contractor
            agrees to comply with all applicable federal, state, and local laws and regulations, including
            food safety standards, when performing services at a Shop's location.
        </p>

        <h2>8. Representations and Warranties</h2>
        <p>The Contractor represents and warrants that they:</p>
        <ul>
            <li>Are at least 18 years of age and legally authorized to work in the United States;</li>
            <li>Have the skills, qualifications, and experience represented in their profile;</li>
            <li>Will perform accepted services in a professional, workmanlike manner;</li>
            <li>Will not engage in any conduct that is unsafe, illegal, or harmful to the Shop, its customers, or {{ company }};</li>
            <li>Will maintain confidentiality regarding the Shop's proprietary information, recipes, and business operations.</li>
        </ul>

        <h2>9. Confidentiality</h2>
        <p>
            The Contractor agrees not to disclose to any third party any confidential business information,
            trade secrets, recipes, customer data, or proprietary processes of a Shop learned during the
            performance of services. This obligation survives termination of this Agreement.
        </p>

        <h2>10. Ratings and Conduct</h2>
        <p>
            Shops may submit ratings and reviews following completed bookings. The Contractor agrees to
            maintain professional conduct at all times. {{ company }} reserves the right to suspend or
            remove a Contractor's account if their behavior poses a risk to Shops, customers, or the
            platform community.
        </p>

        <h2>11. Dispute Resolution Between Contractors and Shops</h2>
        <p>
            {{ company }} is not a party to service disputes between Contractors and Shops. In the event of
            a dispute regarding service quality or payment, both parties agree to attempt resolution in good
            faith through the Platform's dispute process. {{ company }} may, at its discretion, facilitate
            communication but is not obligated to adjudicate or resolve disputes.
        </p>

        <h2>12. Indemnification</h2>
        <p>
            The Contractor agrees to indemnify, defend, and hold harmless {{ company }}, its officers,
            directors, employees, and agents from any claims, damages, losses, or expenses (including
            attorneys' fees) arising from: (a) the Contractor's performance of services; (b) the
            Contractor's violation of this Agreement or any applicable law; or (c) the Contractor's
            negligence or willful misconduct.
        </p>

        <h2>13. Limitation of Liability</h2>
        <p>
            {{ company }}'s liability to any Contractor arising from this Agreement shall not exceed the
            total platform fees paid by {{ company }} to the Contractor in the three months preceding the
            claim. {{ company }} is not liable for loss of income, service cancellations by Shops, or
            payment delays caused by Stripe or banking institutions.
        </p>

        <h2>14. Term and Termination</h2>
        <p>
            This Agreement begins when the Contractor creates a Provider account and continues until
            terminated. Either party may terminate by closing the account or providing written notice.
            {{ company }} may terminate immediately for material breach of this Agreement or the Platform's
            Terms of Service. Termination does not affect compensation for services already completed and
            accepted by the Shop.
        </p>

        <h2>15. Governing Law</h2>
        <p>
            This Agreement is governed by the laws of the State of {{ state }}, without regard to
            conflict-of-law principles. Any disputes arising under this Agreement that cannot be resolved
            informally shall be submitted to binding arbitration in {{ state }} under the American
            Arbitration Association's rules, on an individual basis. The Contractor waives the right to
            participate in any class action or collective proceeding.
        </p>

        <h2>16. Entire Agreement</h2>
        <p>
            This Agreement, together with the Platform's <a href="/legal/terms">Terms of Service</a> and
            <a href="/legal/privacy">Privacy Policy</a>, constitutes the entire agreement between the
            parties regarding the Contractor's use of the Platform and supersedes any prior
            understandings. If any provision of this Agreement is found unenforceable, the remaining
            provisions remain in full effect.
        </p>

        <h2>17. Contact</h2>
        <p>
            Questions about this Agreement? Contact us at
            <a :href="`mailto:${email}`">{{ email }}</a>.
        </p>
    </LegalLayout>
</template>
