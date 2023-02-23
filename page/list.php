<?php

$status = Utils::getUrlParam('status');
NoticiaValidator::validateStatus($status);

$dao = new NoticiaDao();
$search = new NoticiaSearchCriteria();
$search->setStatus($status);

// data for template
$title = "NOTICIA no status: " . Utils::capitalize($status);
$noticias = $dao->find($search);
