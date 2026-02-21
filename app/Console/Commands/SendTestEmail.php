<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendTestEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test {email? : Email address to send to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test email via SendGrid';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email') ?? 'info@shiftfinder.app';

        $this->info('Sending test email to: '.$email);

        try {
            \Illuminate\Support\Facades\Mail::raw(
                'This is a test email from ShiftFinder at '.now()->toDateTimeString(),
                function (\Illuminate\Mail\Message $message) use ($email) {
                    $message->to($email)
                        ->from(config('mail.from.address'), config('mail.from.name'))
                        ->subject('Test Email - '.now()->format('Y-m-d H:i:s'));
                }
            );

            $this->info('✅ Email sent successfully!');
            $this->newLine();
            $this->line('Check your inbox at: '.$email);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Failed to send email: '.$e->getMessage());
            $this->newLine();
            $this->line('Stack trace:');
            $this->line($e->getTraceAsString());

            return Command::FAILURE;
        }
    }
}
