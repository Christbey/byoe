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
            $mail = new \SendGrid\Mail\Mail;
            $mail->setFrom(config('mail.from.address'), config('mail.from.name'));
            $mail->setSubject('SendGrid Test Email - '.now()->format('Y-m-d H:i:s'));
            $mail->addTo($email);
            $mail->addContent(
                'text/plain',
                'This is a test email from ShiftFinder sent via SendGrid at '.now()->toDateTimeString()
            );
            $mail->addContent(
                'text/html',
                '<p>This is a test email from <strong>ShiftFinder</strong> sent via SendGrid at '.now()->toDateTimeString().'</p>'
            );

            $sendgrid = new \SendGrid(env('SENDGRID_API_KEY'));
            $response = $sendgrid->send($mail);

            $this->info('✅ Email sent successfully via SendGrid!');
            $this->line('Response Code: '.$response->statusCode());
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
