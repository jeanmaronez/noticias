<?php

$errors = array();
$noticia = null;
$edit = array_key_exists('id', $_GET);
if ($edit) {
    $noticia = Utils::getNoticiaByGetId();
} else {
    // set defaults
    $noticia = new Noticia();
    $noticia->setPriority(Noticia::PRIORITY_MEDIUM);
    $dueOn = new DateTime("+1 day");
    $dueOn->setTime(0, 0, 0);
    $noticia->setDueOn($dueOn);
}

if (array_key_exists('cancel', $_POST)) {
    // redirect
    Utils::redirect('detail', array('id' => $noticia->getId()));
} elseif (array_key_exists('save', $_POST)) {
    // for security reasons, do not map the whole $_POST['noticia']
    $data = array(
        'title' => $_POST['noticia']['title'],
        'due_on' => $_POST['noticia']['due_on_date'] . ' ' . $_POST['noticia']['due_on_hour'] . ':' . $_POST['noticia']['due_on_minute'] . ':00',
        'priority' => $_POST['noticia']['priority'],
        'description' => $_POST['noticia']['description'],
        'comment' => $_POST['noticia']['comment'],
    );
        ;
    // map
    NoticiaMapper::map($noticia, $data);
    // validate
    $errors = NoticiaValidator::validate($noticia);
    // validate
    if (empty($errors)) {
        // save
        $dao = new NoticiaDao();
        $noticia = $dao->save($noticia);
        Flash::addFlash('NOTICIA salva com sucesso.');
        // redirect
        Utils::redirect('detail', array('id' => $noticia->getId()));
    }
}
