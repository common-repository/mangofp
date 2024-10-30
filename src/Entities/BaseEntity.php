<?php
namespace MangoFp\Entities;

class BaseEntity {
    protected $data;
    protected $className;
    protected function generateUuid() {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

            // 16 bits for "time_mid"
            mt_rand( 0, 0xffff ),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand( 0, 0x0fff ) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand( 0, 0x3fff ) | 0x8000,

            // 48 bits for "node"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
}

    function __construct() {
        $this->data = [
            'id' => $this->generateUuid(),
            'create_time' => (new \DateTime())->format('Y-m-d H:i:s '),
        ];
        //object properties as converted from json-string to object when data is set from database to this object
        $this->objectProperties = [];
        $this->className = 'Base';
	}

	protected function refreshModifiedTime() {
		 $this->data['modify_time'] = (new \DateTime())->format('Y-m-d H:i:s ');
		 return $this;
	}

    public function className() {
        return $this->className;
    }

    public function getDataAsArray() : array {
        return $this->data;
    }

    public function setDataFromArray($newData, $loading = false) {
		$modified = false;
		foreach($this->data as $key => $value) {
            if (isset($newData[$key])) {
                $modified = true;

                $newCurrentValue = $newData[$key];
                if (
                    $this->objectProperties &&
                    $newCurrentValue &&
                    in_array($key, $this->objectProperties) &&
                    !is_array($newCurrentValue)
                ) {
                    try {
                        $newCurrentValue = json_decode($newCurrentValue, true);
                    } catch (\Exception $err) {
                        error_log('Unable to convert json to object: ' . $err->getMessage());
                    }
                }
                $this->data[$key] = $newCurrentValue;
            }
        }
        if ($modified && !$loading) {
            $this->data['modify_time'] = (new \DateTime())->format('Y-m-d H:i:s ');
        }
        return $this;
    }

    public function get(string $key) {
        if (!isset($this->data[$key])) {
            throw new \Error("{$this->className} does not have property $key");
        }
        return $this->data[$key];
    }

}