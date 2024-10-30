<?php

namespace MangoFp\Entities;

class Template extends BaseEntity {
    public function __construct($data = []) {
        parent::__construct();
        $this->data = \array_merge($this->data, [
            'code' => $data['code'] ?? '',
            'modify_time' => $data['modify_time'] ?? '',
			'addresses' => $data['addresses'] ?? [],
			'mainAddresses' => $data['mainAddresses'] ?? [],
			'template' => $data['template'] ?? ''
        ]);
        $this->className = 'Template';
        $this->objectProperties = ['addresses', 'mainAddresses'];
    }

	public static function getDefaultTemplates() {
        return [
            'INPROGRESS' => [
                'mainAddresses' => ['[contactEmail]'],
                'addresses' => [],
                'template' => "<p>Hi,</p><p><br></p><p>Thank you for your interest in our services.</p><p>Here's what we can do:</p><p><em>&lt;describe your services&gt;</em></p><p><br></p><p>Pricelist of our services:</p><p><em>&lt;insert pricelist of your services</em>&gt;</p><p><br></p><p><br></p><p>kind regards,</p><p><em>&lt;Insert Your name&gt;</em></p><p><br></p>",
            ],
            'ACCEPTED' => [
                'mainAddresses' => ['[contactEmail]'],
                'addresses' => [],
                'template' => "<p>Hi,</p><p><br></p><p>Thank you for your order. </p><p><em>&lt;Add relevant information&gt;</em></p><p><br></p><p>kind regards,</p><p><em>&lt;Insert Your name&gt;</em></p>",
            ],
        ];
	}
}
