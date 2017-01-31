<?php namespace Tberk\Laravel5Mailjet\Transport;

use Swift_Transport;
use GuzzleHttp\Client;
use Swift_Mime_Message;
use Swift_Events_EventListener;

class MailjetTransport implements Swift_Transport
{

    /**
     * The Mailjet API key.
     *
     * @var string
     */
    protected $key;

    /**
     * The Mailjet API secret.
     *
     * @var string
     */
    protected $secret;

    /**
     * THe Mailjet API end-point.
     *
     * @var string
     */
    protected $url;

    /**
     * Create a new Mailgun transport instance.
     *
     * @param  string $key
     * @param  string $secret
     * @return void
     */
    public function __construct($key, $secret)
    {
        $this->key = $key;
        $this->secret = $secret;
        $this->url = 'https://api.mailjet.com/v3/send';
    }

    /**
     * {@inheritdoc}
     */
    public function isStarted()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function start()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function stop()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function send(Swift_Mime_Message $message, &$failedRecipients = null)
    {
        $client = $this->getHttpClient();
        return $client->post($this->url, [
            'json' => $this->getBody($message),
            'auth' => [$this->key, $this->secret]
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function registerPlugin(Swift_Events_EventListener $plugin)
    {
        //
    }

    /**
     * Get the "to" payload field for the API request.
     *
     * @param  \Swift_Mime_Message $message
     * @return array
     */
    protected function getTo(Swift_Mime_Message $message)
    {
        $formatted = [];

        $contacts = array_merge(
            (array)$message->getTo(), (array)$message->getCc(), (array)$message->getBcc()
        );

        foreach ($contacts as $address => $display) {
            $formatted[] = $display ? $display . " <$address>" : $address;
        }

        return implode(',', $formatted);
    }

    /**
     * Get the "from" payload field for the API request.
     *
     * @param  \Swift_Mime_Message $message
     * @return array
     */
    protected function getFrom(Swift_Mime_Message $message)
    {
        $formatted = [];
        foreach ($message->getFrom() as $address => $display) {
            $formatted[] = $display ? $display . " <$address>" : $address;
        }

        return $formatted[0];
    }

    /**
     * Get the "body" payload field for the Guzzle request.
     *
     * @param Swift_Mime_Message $message
     * @return PostBody
     */
    protected function getBody(Swift_Mime_Message $message)
    {
        $messageHtml = $message->getBody();
        $body = [
            'FromEmail' => implode(',', array_keys($message->getFrom())),
            'FromName' => implode(',', $message->getFrom()),
            'Subject' => $message->getSubject(),
            'Recipients' => [[
                'Email' => implode(',', array_keys($message->getTo())),
                'Name' => implode(',', $message->getTo())
            ]],
            'Html-part' => $messageHtml
        ];

        if ($message->getChildren()) {
            foreach ($message->getChildren() as $child) {
                switch (get_class($child)) {
                    case 'Swift_Attachment':
                    case 'Swift_Image':
                        if (!array_key_exists('Attachments', $body)) {
                            $body['Attachments'] = [];
                        }
                        array_push($body['Attachments'], [
                            'Content-Type' => $child->getContentType(),
                            'Filename' => $child->getFilename(),
                            'content' => base64_encode($child->getBody())
                        ]);
                        break;
                    case 'Swift_MimePart':
                        switch ($child->getContentType()) {
                            case 'text/plain':
                                $body['Text-part'] = $child->getBody();
                                break;
                            case 'text/html':
                                $body['Html-part'] = $child->getBody();
                                break;
                        }
                        break;
                }
            }
        }

        return $body;
    }

    /**
     * Get a new HTTP client instance.
     *
     * @return \GuzzleHttp\Client
     */
    protected function getHttpClient()
    {
        return new Client;
    }

    /**
     * Get the API key being used by the transport.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Set the API key being used by the transport.
     *
     * @param  string $key
     * @return void
     */
    public function setKey($key)
    {
        return $this->key = $key;
    }

    /**
     * Get the API secret being used by the transport.
     *
     * @return string
     */
    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * Set the API secret being used by the transport.
     *
     * @param  string $secret
     * @return void
     */
    public function setSecret($secret)
    {
        return $this->secret = $secret;
    }

}
