<?php
namespace MangoFp\UseCases;
use MangoFp\Entities\Message;
use MangoFp\Entities\Option;
use MangoFp\Entities\Label;
use MangoFp\Entities\HistoryItem;
use MangoFp\Entities\Template;

interface iStorage {
    public function storeMessage(Message $message);
    public function insertMessage(Message $message);
    public function messageExists(Message $message);
    public function fetchMessage(string $id);
    public function fetchMessages();
    public function fetchLabelByName(string $labelName);
    public function insertLabel(Label $label);
    public function getLabelTag();
    public function fetchLabels();
    public function insertHistoryItem(HistoryItem $historyItem);
    public function fetchItemHistory(string $id);
	public function storeOption(Option $optionObj);
	public function fetchOption(string $code);
	public function storeTemplate(Template $emailTemplate);
	public function fetchTemplate(string $code);
    public function getDefaultLabel($meta);
}