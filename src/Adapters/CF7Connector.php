<?php
namespace MangoFp;

class CF7Connector {
    public static function actionCF7Submit( $result ) {
        $submission = \WPCF7_Submission::get_instance();

        if (
            !$submission ||
            !$posted_data = $submission->get_posted_data()
        ) {
            error_log('No posted data');
            return;
        }

        $post_id = (int) $submission->get_meta('container_post_id');
        $post = get_post( $post_id );
        $pageTitle = $post->post_title;

        $useCase = new UseCases\MessageUseCase(
            new AdminRoutes(),
            new MessagesDB()
        );

        $meta = [];
        $contactForm = $submission->get_contact_form();

        if ($contactForm) {
            $meta['formId'] = $contactForm->id();
            $meta['formName'] = $contactForm->name();
            $meta['formTitle'] = $contactForm->title();
        }

        if ($post_id) {
            $meta['pageId'] = $post_id;
        }
        error_log('Posted data:' . print_r($meta, 1));
        $useCase->parseContentAndInsertToDatabase($posted_data, $meta);
    }
}