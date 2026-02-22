<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import Heading from '@/components/Heading.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';

const page = usePage();
const user = computed(() => page.props.auth.user);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Support', href: '/settings/support' },
];

const supportEmail = 'support@shiftfinder.com';

const emailSubject = encodeURIComponent('Bug Report / Support Request');
const emailBody = encodeURIComponent(`Hi Support Team,

I need help with:
[Please describe the issue you're experiencing]

---
System Information:
User ID: ${user.value?.id}
Email: ${user.value?.email}
Browser: ${navigator.userAgent}
Page URL: ${window.location.href}
Timestamp: ${new Date().toISOString()}
`);

const mailtoLink = `mailto:${supportEmail}?subject=${emailSubject}&body=${emailBody}`;
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Support" />

        <h1 class="sr-only">Support</h1>

        <SettingsLayout>
            <div class="space-y-6">
                <Heading
                    variant="small"
                    title="Support & Help"
                    description="Get help with bugs or issues"
                />

                <Card>
                    <CardContent class="space-y-6 pt-6">
                        <div>
                            <h3 class="text-lg font-semibold mb-2">Report a Bug</h3>
                            <p class="text-sm text-muted-foreground mb-4">
                                If you've encountered a bug or issue, please let us know.
                                Click the button below to send us an email with your bug report.
                            </p>
                            <Button as-child>
                                <a :href="mailtoLink">
                                    Contact Support
                                </a>
                            </Button>
                        </div>

                        <div class="border-t pt-6">
                            <h3 class="text-lg font-semibold mb-2">What to Include</h3>
                            <ul class="list-disc list-inside space-y-2 text-sm text-muted-foreground">
                                <li>A clear description of the problem</li>
                                <li>Steps to reproduce the issue</li>
                                <li>What you expected to happen</li>
                                <li>What actually happened</li>
                                <li>Screenshots if applicable</li>
                            </ul>
                        </div>

                        <div class="border-t pt-6">
                            <h3 class="text-lg font-semibold mb-2">Response Time</h3>
                            <p class="text-sm text-muted-foreground">
                                We aim to respond to all support requests within 24-48 hours during business days.
                                For urgent issues, please mention "URGENT" in your email subject line.
                            </p>
                        </div>

                        <div class="rounded-lg border border-primary/20 bg-primary/5 p-4">
                            <p class="text-sm">
                                <strong class="text-foreground">Support Email:</strong>
                                <a :href="`mailto:${supportEmail}`" class="text-primary hover:underline ml-1">
                                    {{ supportEmail }}
                                </a>
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
