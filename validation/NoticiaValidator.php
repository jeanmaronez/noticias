<?php

final class NoticiaValidator {

    private function __construct() {
    }

    /**
     * Validate the given {@link Noticia} instance.
     * @param Noticia $noticia {@link Noticia} instance to be validated
     * @return array array of {@link Error} s
     */
    public static function validate(Noticia $noticia) {
        $errors = array();
        if (!$noticia->getCreatedOn()) {
            $errors[] = new Error('createdOn', 'Data de criação inválida.');
        }
        if (!$noticia->getLastModifiedOn()) {
            $errors[] = new Error('lastModifiedOn', 'Data de modificação inválida.');
        }
        if (!trim($noticia->getTitle())) {
            $errors[] = new Error('title', 'Titulo não pode estar em branco.');
        }
        if (!$noticia->getDueOn()) {
            $errors[] = new Error('dueOn', 'Data programada em branco ou inválida.');
        }
        if (!trim($noticia->getPriority())) {
            $errors[] = new Error('priority', 'Prioridade não pode estar em branco.');
        } elseif (!self::isValidPriority($noticia->getPriority())) {
            $errors[] = new Error('priority', 'Prioridade marcada inválida.');
        }
        if (!trim($noticia->getStatus())) {
            $errors[] = new Error('status', 'Status não pode estar em branco..');
        } elseif (!self::isValidStatus($noticia->getStatus())) {
            $errors[] = new Error('status', 'Status marcado inválido.');
        }
        return $errors;
    }

    /**
     * Validate the given status.
     * @param string $status status to be validated
     * @throws Exception if the status is not known
     */
    public static function validateStatus($status) {
        if (!self::isValidStatus($status)) {
            throw new Exception('Status desconhecido: ' . $status);
        }
    }

    /**
     * Validate the given priority.
     * @param int $priority priority to be validated
     * @throws Exception if the priority is not known
     */
    public static function validatePriority($priority) {
        if (!self::isValidPriority($priority)) {
            throw new Exception('Prioridade desconhecida: ' . $priority);
        }
    }

    private static function isValidStatus($status) {
        return in_array($status, Noticia::allStatuses());
    }

    private static function isValidPriority($priority) {
        return in_array($priority, Noticia::allPriorities());
    }

}
