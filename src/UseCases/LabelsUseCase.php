<?php
namespace MangoFp\UseCases;
use MangoFp\Entities\Label;

class LabelsUseCase {
    function __construct(iOutput $output, iStorage $storage) {
        $this->output = $output;
        $this->storage = $storage;
    }

    public function fetchAllLabelsToOutput() {
        $labels = $this->storage->fetchLabels();

        if (!$labels || !\is_array($labels)) {
            $labels = [];
            //return $this->output->outputError('ERROR => unable to read labels list', iOutput::ERROR_FAILED);
        }

        $data = [];
        foreach ($labels as $label) {
            $data[] = [
                'id' => $label->get('id'),
                'name' => $label->get('labelName')
            ];
        }
        return $this->output->outputResult(['labels' => $data]);
    }

    public function fetchAllStatesToOutput() {
        $data = [
            'NEW' => [
                order => 1,
                code => 'NEW',
                state => 'Uus',
                action => 'Määra uueks',
                next => [
                    'REGISTERED',
                    'WAIT4CONF',
                    'WAIT4NEW',
                    'WAIT4ACCEPT',
                    'CANCELLED',
                    'NEWSLETTER',
                    'ARCHIVED',
                ],
            ],
            'REGISTERED' => [
                order => 2,
                code => 'REGISTERED',
                state => 'Registreeritud',
                action => 'Registreeri',
                next => ['NOTIFIED', 'ARCHIVED', 'CANCELLED'],
            ],
            'WAIT4CONF' => [
                order => 3,
                code => 'WAIT4CONF',
                state => 'TTA kaudu',
                action => 'TTA kaudu',
                next => ['CONFRECEIVED', 'CANCELLED'],
            ],
            'WAIT4NEW' => [
                order => 4,
                code => 'WAIT4NEW',
                state => 'Ooteleht',
                action => 'Ooteleht',
                next => ['REGISTERED', 'WAIT4ACCEPT', 'CANCELLED'],
            ],
            'WAIT4ACCEPT' => [
                order => 5,
                code => 'WAIT4ACCEPT',
                state => 'Aeg pakutud',
                action => 'Paku uus aeg',
                next => ['REGISTERED', 'WAIT4ACCEPT', 'CANCELLED'],
            ],
            'CONFRECEIVED' => [
                order => 6,
                code => 'CONFRECEIVED',
                state => 'Kinnitus saabunud',
                action => 'Kinnitus saabunud',
                next => ['REGISTERED', 'NOTIFIED', 'ARCHIVED', 'CANCELLED'],
            ],
            'NOTIFIED' => [
                order => 7,
                code => 'NOTIFIED',
                state => 'Teade saadetud',
                action => 'Saada meeldetuletus',
                next => ['FBASKED', 'ARCHIVED'],
            ],
            'FBASKED' => [
                order => 8,
                code => 'FBASKED',
                state => 'Tagasiside küsitud',
                action => 'Küsi tagasiside',
                next => ['ARCHIVED'],
            ],
            'NEWSLETTER' => [
                order => 9,
                code => 'NEWSLETTER',
                state => 'Uudiskiri',
                action => 'Uudiskiri',
                next => ['ARCHIVED'],
            ],
            'ARCHIVED' => [
                order => 10,
                code => 'ARCHIVED',
                state => 'Arhiveeritud',
                action => 'Arhiveeri',
                next => [],
            ],
            'CANCELLED' => [
                order => 11,
                code => 'CANCELLED',
                state => 'Katkestatud',
                action => 'Katkesta',
                next => ['ARCHIVED'],
            ]
        ];

        return $this->output->outputResult(['states' => $data]);
    }

    public function fetchAllTemplatesToOutput() {
        $templates = [
                REGISTERED => [
                    template =>
                        'Tere!\n\nSuur tänu! Olete koolitusele registreeritud.\nTäpsema info ja arve saadame enne koolituse algust e-mailile.\n\nTervitustega\nSirli Järviste\n_______________\nN.O.R.T Koolitus\nVaksali 17a, (407), Tartu\nhttps://www.nort.ee\ninfo@nort.ee\ntel. 7428000',
                ],
                WAIT4CONF => [
                    template =>
                        'Tere!\n\nSuur tänu! Olete koolitusele registreeritud.\n\nSaadan Töötukassasse ära registreerimisteate ja annan teada kui neilt kinnitus saabub.\n\nTervitustega\nSirli Järviste\n_______________\nN.O.R.T Koolitus\nVaksali 17a, (407), Tartu\nhttps://www.nort.ee\ninfo@nort.ee\ntel. 7428000',
                ],
                CONFRECEIVED => [
                    template =>
                        'Tere!\n\nTöötukassalt saabus kinnitus, sellega on nüüd kõik korras ja jääb ainult koolitust oodata.\n\nSaadan enne koolituse algust veel täpsustava infomeili.\n\Tervitades\nSirli Järviste\n_______________\nN.O.R.T Koolitus\nVaksali 17a, (407), Tartu\nhttps://www.nort.ee\ninfo@nort.ee\ntel. 7428000',
                ],
                WAIT4ACCEPT => [
                    template =>
                        'Tere!\n\nSuur tänu, et tunnete huvi meie koolituse vastu. Kuna järgmine koolitusaeg ei ole hetkel veel paigas, siis jätame Teid ootelehele ja anname teada kui koolitusaeg selgub.\n\nTervitustega\nSirli Järviste\n_______________\nN.O.R.T Koolitus\nVaksali 17a, (407), Tartu\nhttps://www.nort.ee\ninfo@nort.ee\ntel. 7428000',
                ],
                WAIT4NEW => [
                    template =>
                        'Tere!\n\nSuur tänu, et tunnete huvi meie koolituse vastu. Kuna järgmine koolitusaeg ei ole hetkel veel paigas, siis jätame Teid ootelehele ja anname teada kui koolitusaeg selgub.\n\nTervitustega\nSirli Järviste\n_______________\nN.O.R.T Koolitus\nVaksali 17a, (407), Tartu\nhttps://www.nort.ee\ninfo@nort.ee\ntel. 7428000',
                ],
                NOTIFIED => [
                    template =>
                        'Tere!\n\nOotame Teid esmaspäeval, 06. novembril kell 10.00, Exceli täiendkoolituse esimesele päevale.\n\nKoolitus toimub NORT Koolituse arvutiklassis, Vaksali 17a, ruum 407, Tartu (sissepääs Vaksali tänavalt, lillepoe ja kohvikuga samast uksest, liftiga 4.korrusele, asume otse lifti vastas.)\n\nPanin kaasa ka koolitusarve. Kui midagi oleks selles vaja muuta, siis andke palun teada.\n\nParkimine -  tänava ääres kellaga 90 min tasuta ja alates kella 18.00-st tasuta. Raudtee äärses parklas ja Tiigi tn äärses parklas on kogu aeg tasuta. Lähim linnaliini peatus on „Vaksali“.\n_______________\nN.O.R.T Koolitus\nVaksali 17a, (407), Tartu\nhttps://www.nort.ee\ninfo@nort.ee\ntel. 7428000',
                ],
                NEWSLETTER => [
                    template =>
                        'Tere!\n\nAitäh, et liitusite meie uudiskirjaga!.\n\nTänutäheks pakume Teile koolitusel osalemiseks soodustust -10%. (Soodustuse saamiseks, kirjutage koolitusele registreerumisel lisainfo lahtrisse sõna - "uudiskiri") Meie koolituskalendri leiate aadressilt https://nort.ee/koolituskalender/ \n\nKui on küsimusi, siis vastan meeleldi.\n\nTervitustega\nSirli Järviste\n_______________\nN.O.R.T Koolitus\nVaksali 17a, (407), Tartu\nhttps://www.nort.ee\ninfo@nort.ee\ntel. 7428000',
                ],
        ];
        return $this->output->outputResult(['templates' => $templates]);
    }
}