<?php

/**
 * The French language file - try to be the best of French
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Sylvain Corvaisier <cocky@cocky.fr>
 * @author Thomas Bassetto <tbassetto@gmail.com>
 * @author Laurent J.V. Dubois <laurent.dubois@ljvd.com>
 * @author Cédric Frayssinet
 * @copyright 2004-2023 phpMyFAQ Team
 * @license https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2004-02-19
 * @codingStandardsIgnoreFile
 */

/**
 *                !!! IMPORTANT NOTE !!!
 * Please consider following while defining new vars:
 * - one variable definition per line !!!
 * - the perfect case is to define a scalar string value
 * - if some dynamic content is needed, use sprintf() syntax
 * - arrays are allowed but not recommended
 * - no comments at the end of line after the var definition
 * - do not use '=' char in the array keys
 *   (eq. $PMF_LANG["a=b"] is not allowed)
 *
 *  Please be consistent with this format as we need it for
 *  the translation tool to work properly
 */

$PMF_LANG["metaCharset"] = "UTF-8";
$PMF_LANG["metaLanguage"] = "fr";
$PMF_LANG["language"] = "French";
// ltr: left to right (e.g. English language); rtl: right to left (e.g. Arabic language)
$PMF_LANG["dir"] = "ltr";
$PMF_LANG["nplurals"] = "2";

// Navigation
$PMF_LANG["msgCategory"] = "Catégories";
$PMF_LANG["msgShowAllCategories"] = "Toutes les catégories";
$PMF_LANG["msgSearch"] = "Recherche";
$PMF_LANG["msgAddContent"] = "Ajouter une FAQ";
$PMF_LANG["msgQuestion"] = "Poser une question";
$PMF_LANG["msgOpenQuestions"] = "Questions ouvertes";
$PMF_LANG["msgHelp"] = "Aide";
$PMF_LANG["msgContact"] = "Contact";
$PMF_LANG["msgHome"] = "Accueil";
$PMF_LANG["msgNews"] = " Actualités";
$PMF_LANG["msgUserOnline"] = " Utilisateurs en ligne";
$PMF_LANG["msgBack2Home"] = "Retour à l'accueil";

// Contentpages
$PMF_LANG["msgFullCategories"] = "Catégories";
$PMF_LANG["msgFullCategoriesIn"] = "Catégories contenant des FAQs";
$PMF_LANG["msgSubCategories"] = "Sous-catégories";
$PMF_LANG["msgEntries"] = "FAQs";
$PMF_LANG["msgEntriesIn"] = "Entrées dans la catégorie ";
$PMF_LANG["msgViews"] = "affichage";
$PMF_LANG["msgPage"] = "Page ";
$PMF_LANG["msgPages"] = " Pages";
$PMF_LANG["msgPrevious"] = "Précédent";
$PMF_LANG["msgNext"] = "Suivant";
$PMF_LANG["msgCategoryUp"] = "Remonter à la catégorie supérieure";
$PMF_LANG["msgLastUpdateArticle"] = "Dernière mise à jour : ";
$PMF_LANG["msgAuthor"] = "Auteur ";
$PMF_LANG["msgPrinterFriendly"] = "version imprimable";
$PMF_LANG["msgPrintArticle"] = "Imprimer cet article";
$PMF_LANG["msgMakeXMLExport"] = "Exporter en fichier XML  ";
$PMF_LANG["msgAverageVote"] = "Moyenne des notes";
$PMF_LANG["msgVoteUsability"] = "Noter cette FAQ";
$PMF_LANG["msgVoteFrom"] = "sur";
$PMF_LANG["msgVoteBad"] = "complètement inutile";
$PMF_LANG["msgVoteGood"] = "indispensable";
$PMF_LANG["msgVotings"] = "Votes ";
$PMF_LANG["msgVoteSubmit"] = "Vote";
$PMF_LANG["msgVoteThanks"] = "Merci d'avoir voté !";
$PMF_LANG["msgYouCan"] = "Vous pouvez ";
$PMF_LANG["msgWriteComment"] = "commenter cette FAQ";
$PMF_LANG["msgShowCategory"] = "Affichage du contenu : ";
$PMF_LANG["msgCommentBy"] = "Commenté par ";
$PMF_LANG["msgCommentHeader"] = "Commenter cet article ";
$PMF_LANG["msgYourComment"] = "Votre commentaire :";
$PMF_LANG["msgCommentThanks"] = "Merci pour votre commentaire !";
$PMF_LANG["msgSeeXMLFile"] = "ouvrir fichier XML ";
$PMF_LANG["msgSend2Friend"] = "Envoyer à un ami ";
$PMF_LANG["msgS2FName"] = "Votre nom :";
$PMF_LANG["msgS2FEMail"] = "Votre adresse e-mail :";
$PMF_LANG["msgS2FFriends"] = "Votre ami :";
$PMF_LANG["msgS2FEMails"] = ". adresse e-mail :";
$PMF_LANG["msgS2FText"] = "Le texte suivant va être envoyé :";
$PMF_LANG["msgS2FText2"] = "Vous trouverez cette FAQ à l'adresse suivante :";
$PMF_LANG["msgS2FMessage"] = "Message supplémentaire pour votre ami :";
$PMF_LANG["msgS2FButton"] = "Envoyer";
$PMF_LANG["msgS2FThx"] = "Merci pour votre recommandation !";
$PMF_LANG["msgS2FMailSubject"] = "Recommandation de ";

// Search
$PMF_LANG["msgSearchWord"] = "Mots-clés";
$PMF_LANG["msgSearchFind"] = "Résultat de recherche pour ";
$PMF_LANG["msgSearchAmount"] = " résultat de recherche";
$PMF_LANG["msgSearchAmounts"] = " résultats de recherche";
$PMF_LANG["msgSearchCategory"] = "Catégorie : ";
$PMF_LANG["msgSearchContent"] = "Contenu : ";

// new Content
$PMF_LANG["msgNewContentHeader"] = "Proposer une nouvelle entrée";
$PMF_LANG["msgNewContentAddon"] = "Votre proposition ne va pas être publiée immédiatement, elle devra être validée par un administrateur. Les champs requis sont <strong>votre nom</strong>, <strong>votre adresse e-mail</strong>, <strong>la catégorie</strong>, <strong>votre question</strong> et <strong>votre réponse</strong>. Veuillez séparer chaque mot-clé par une virgule.";
$PMF_LANG["msgNewContentName"] = "Votre nom";
$PMF_LANG["msgNewContentMail"] = "Votre adresse e-mail";
$PMF_LANG["msgNewContentCategory"] = "Catégorie";
$PMF_LANG["msgNewContentTheme"] = "Votre question";
$PMF_LANG["msgNewContentArticle"] = "Votre réponse";
$PMF_LANG["msgNewContentKeywords"] = "Mots-clés";
$PMF_LANG["msgNewContentLink"] = "Lien vers cette FAQ";
$PMF_LANG["msgNewContentSubmit"] = "Envoyer";
$PMF_LANG["msgInfo"] = "Plus d'informations : ";
$PMF_LANG["msgNewContentThanks"] = "Merci pour votre suggestion !";

// ask Question
$PMF_LANG["msgNewQuestion"] = "Posez votre question ci-dessous : ";
$PMF_LANG["msgAskCategory"] = "Catégorie :";
$PMF_LANG["msgAskYourQuestion"] = "Votre question :";
$PMF_LANG["msgAskThx4Mail"] = "Merci pour votre question !";
$PMF_LANG["msgDate_User"] = "Date / Utilisateur";
$PMF_LANG["msgQuestion2"] = "Question";
$PMF_LANG["msg2answer"] = "Répondre";
$PMF_LANG["msgQuestionText"] = "Ici, vous pouvez voir les questions posées par d'autres personnes. Vous pouvez y proposer une réponse, qui sera peut-être insérée dans la FAQ.";
$PMF_LANG["msgNoQuestionsAvailable"] = "Actuellement il n'y a pas de nouvelle question.";

// Contact
$PMF_LANG["msgContactEMail"] = "Envoyer un e-mail au proriétaire de la FAQ";
$PMF_LANG["msgMessage"] = "Votre message";

// Homepage
$PMF_LANG["msgTopTen"] = "FAQ les plus populaires";
$PMF_LANG["msgHomeThereAre"] = "Il y a ";
$PMF_LANG["msgHomeArticlesOnline"] = " FAQs en ligne";
$PMF_LANG["msgNoNews"] = "Pas d'actualités.";
$PMF_LANG["msgLatestArticles"] = "Dernières FAQs";

// Email notification
$PMF_LANG["msgMailThanks"] = "Merci pour votre proposition pour la FAQ.";
$PMF_LANG["msgMailCheck"] = "Il y a une nouvelle publication dans la FAQ ! Veuillez consulter la section d'administration !";
$PMF_LANG["msgMailContact"] = "Votre message a été envoyé à l'administrateur !";

// Error messages
$PMF_LANG["err_noDatabase"] = "Pas de connexion à la base de données.";
$PMF_LANG["err_noHeaders"] = "Aucune catégorie trouvée.";
$PMF_LANG["err_noArticles"] = "Aucun résultat.";
$PMF_LANG["err_badID"] = "Mauvais identifiant.";
$PMF_LANG["err_noTopTen"] = "Pas de FAQ populaire actuellement.";
$PMF_LANG["err_nothingFound"] = "Pas d'entrée trouvée.";
$PMF_LANG["err_SaveEntries"] = "Les champs requis sont <strong>votre nom</strong>, <strong>votre adresse e-mail</strong>, <strong>la catégorie</strong>, <strong>la question</strong>, <strong>votre réponse</strong> et, si requis, le code <strong><a href=\"https://fr.wikipedia.org/wiki/Captcha\" title=\"En savoir plus sur les Captcha sur Wikipedia\" target=\"_blank\">Captcha</a></strong> !";
$PMF_LANG["err_SaveComment"] = "Les champs requis sont <strong>votre nom</strong>, <strong>votre adresse e-mail</strong>, <strong>vos commentaire</strong> et, si requis, le code <strong><a href=\"https://fr.wikipedia.org/wiki/Captcha\" title=\"En savoir plus sur les Captcha sur Wikipedia\" target=\"_blank\">Captcha</a></strong> !";
$PMF_LANG["err_VoteTooMuch"] = "Nous ne comptons pas les votes multiples.";
$PMF_LANG["err_noVote"] = "Vous n'avez pas noté la question !";
$PMF_LANG["err_noMailAdress"] = "Votre adresse e-mail est incorecte.";
$PMF_LANG["err_sendMail"] = "Les champs requis sont  <strong>votre nom</strong>, <strong>votre adresse e-mail</strong>, <strong>votre question</strong> et, si requis, le code <strong><a href=\"https://fr.wikipedia.org/wiki/Captcha\" title=\"En savoir plus sur les Captcha sur Wikipedia\" target=\"_blank\">Captcha</a></strong> !";

// Search help
$PMF_LANG["help_search"] = "<strong>Recherche d'enregistrements :</strong><br>Avec une saisie comme <strong style=\"color: Red;\">Terme1 Terme2</strong> il est possible de chercher plusieurs termes. <strong>Note :</strong> Votre critère de recherche doit contenir au moins 4 lettres sinon votre requête sera rejetée.";

// Menu
$PMF_LANG["ad"] = "SECTION ADMIN";
$PMF_LANG["ad_menu_user_administration"] = "Utilisateurs";
$PMF_LANG["ad_menu_entry_aprove"] = "Approuver une FAQ";
$PMF_LANG["ad_menu_entry_edit"] = "Editer une FAQ";
$PMF_LANG["ad_menu_categ_add"] = "Ajouter une catégorie";
$PMF_LANG["ad_menu_categ_edit"] = "Catégories";
$PMF_LANG["ad_menu_news_add"] = "Ajouter une actualité";
$PMF_LANG["ad_menu_news_edit"] = "Actualités";
$PMF_LANG["ad_menu_open"] = "Questions ouvertes";
$PMF_LANG["ad_menu_stat"] = "Statistiques";
$PMF_LANG["ad_menu_cookie"] = "Cookies";
$PMF_LANG["ad_menu_session"] = "Voir les sessions";
$PMF_LANG["ad_menu_adminlog"] = "Voir le journal d'admin";
$PMF_LANG["ad_menu_passwd"] = "Modifier le mot de passe";
$PMF_LANG["ad_menu_logout"] = "Déconnexion";
$PMF_LANG["ad_menu_startpage"] = "Page d'accueil";

// Messages
$PMF_LANG["ad_msg_identify"] = "Veuillez-vous identifier.";
$PMF_LANG["ad_msg_passmatch"] = "Les deux mots de passe doivent être <strong>identiques</strong>!";
$PMF_LANG["ad_msg_savedsuc_1"] = "Le profil de";
$PMF_LANG["ad_msg_savedsuc_2"] = "a été correctement enregistré.";
$PMF_LANG["ad_msg_mysqlerr"] = "Suite à une erreur de <strong>base de données</strong> le profil n'a pas pu être enregistré.";
$PMF_LANG["ad_msg_noauth"] = "Vous n'êtes pas autorisé.";

// General
$PMF_LANG["ad_gen_page"] = "Page";
$PMF_LANG["ad_gen_of"] = "de";
$PMF_LANG["ad_gen_lastpage"] = "Page précédente";
$PMF_LANG["ad_gen_nextpage"] = "Page suivante";
$PMF_LANG["ad_gen_save"] = "Enregistrer";
$PMF_LANG["ad_gen_reset"] = "Réinitialiser";
$PMF_LANG["ad_gen_yes"] = "Oui";
$PMF_LANG["ad_gen_no"] = "Non";
$PMF_LANG["ad_gen_top"] = "Haut de page";
$PMF_LANG["ad_gen_ncf"] = "Pas de catégorie trouvée!";
$PMF_LANG["ad_gen_delete"] = "Supprimer";
$PMF_LANG["ad_gen_or"] = "ou";

// User administration
$PMF_LANG["ad_user"] = "Gestion des utilisateurs";
$PMF_LANG["ad_user_username"] = "Utilisateurs enregistrés";
$PMF_LANG["ad_user_rights"] = "Droits";
$PMF_LANG["ad_user_edit"] = "Éditer";
$PMF_LANG["ad_user_delete"] = "Supprimer";
$PMF_LANG["ad_user_add"] = "Ajouter un utilisateur";
$PMF_LANG["ad_user_profou"] = "Profil de l'utilisateur";
$PMF_LANG["ad_user_name"] = "Nom";
$PMF_LANG["ad_user_password"] = "Mot de passe";
$PMF_LANG["ad_user_confirm"] = "Confirmation";
$PMF_LANG["ad_user_del_1"] = "L'utilisateur";
$PMF_LANG["ad_user_del_2"] = "doit être supprimé ?";
$PMF_LANG["ad_user_del_3"] = "Êtes-vous sûr ?";
$PMF_LANG["ad_user_deleted"] = "L'utilisateur a été correctement supprimé.";
$PMF_LANG["ad_user_checkall"] = "Tout sélectionner";

// Contribution management
$PMF_LANG["ad_entry_aor"] = "Administration des FAQ";
$PMF_LANG["ad_entry_id"] = "Identifiant";
$PMF_LANG["ad_entry_topic"] = "Titre";
$PMF_LANG["ad_entry_action"] = "Action";
$PMF_LANG["ad_entry_edit_1"] = "Éditer l'enregistrement";
$PMF_LANG["ad_entry_edit_2"] = "";
$PMF_LANG["ad_entry_theme"] = "Question";
$PMF_LANG["ad_entry_content"] = "Réponse";
$PMF_LANG["ad_entry_keywords"] = "Mots-clés";
$PMF_LANG["ad_entry_author"] = "Auteur";
$PMF_LANG["ad_entry_category"] = "Catégorie";
$PMF_LANG["ad_entry_active"] = "Visible";
$PMF_LANG["ad_entry_date"] = "Date";
$PMF_LANG["ad_entry_status"] = "Statut de la FAQ";
$PMF_LANG["ad_entry_changed"] = "Modifié ?";
$PMF_LANG["ad_entry_changelog"] = "Changements";
$PMF_LANG["ad_entry_commentby"] = "Commenté par";
$PMF_LANG["ad_entry_comment"] = "Commentaires";
$PMF_LANG["ad_entry_save"] = "Enregistrer";
$PMF_LANG["ad_entry_delete"] = "Supprimer";
$PMF_LANG["ad_entry_delcom_1"] = "Êtes-vous sûr que le commentaire de l'utilisateur";
$PMF_LANG["ad_entry_delcom_2"] = "doit être supprimé ?";
$PMF_LANG["ad_entry_commentdelsuc"] = "Le commentaire a été <strong>correctement</strong> supprimé.";
$PMF_LANG["ad_entry_back"] = "Retour";
$PMF_LANG["ad_entry_commentdelfail"] = "Le commentaire n'a <strong>pas</strong> été supprimé.";
$PMF_LANG["ad_entry_savedsuc"] = "Les modifications ont étés correctement enregistrées.";
$PMF_LANG["ad_entry_savedfail"] = "Une erreur de <strong>base de données</strong> est survenue.";
$PMF_LANG["ad_entry_del_1"] = "Êtes-vous sûr que l'article";
$PMF_LANG["ad_entry_del_2"] = "de ";
$PMF_LANG["ad_entry_del_3"] = "doit être supprimé ?";
$PMF_LANG["ad_entry_delsuc"] = "Entrée <strong>correctement</strong> supprimée.";
$PMF_LANG["ad_entry_delfail"] = "Entrée n'a <strong>pas</strong> été supprimée!";
$PMF_LANG["ad_entry_back"] = "Retour";

// News management
$PMF_LANG["ad_news_header"] = "Titre de l'actualité";
$PMF_LANG["ad_news_text"] = "Texte";
$PMF_LANG["ad_news_link_url"] = "Lien";
$PMF_LANG["ad_news_link_title"] = "Titre du lien :";
$PMF_LANG["ad_news_link_target"] = "Destination du lien";
$PMF_LANG["ad_news_link_window"] = "Lien dans une nouvelle fenêtre";
$PMF_LANG["ad_news_link_faq"] = "Lien dans la FAQ";
$PMF_LANG["ad_news_add"] = "Ajouter une actualité";
$PMF_LANG["ad_news_id"] = "#";
$PMF_LANG["ad_news_headline"] = "Titre";
$PMF_LANG["ad_news_date"] = "Date";
$PMF_LANG["ad_news_action"] = "Action";
$PMF_LANG["ad_news_update"] = "Éditer";
$PMF_LANG["ad_news_delete"] = "Supprimer";
$PMF_LANG["ad_news_nodata"] = "Pas de donnée trouvée dans la base";
$PMF_LANG["ad_news_updatesuc"] = "L'actualité a été enregistrée.";
$PMF_LANG["ad_news_del"] = "Êtes-vous sûr de vouloir supprimer cette actualité ?";
$PMF_LANG["ad_news_yesdelete"] = "Oui, supprimer !";
$PMF_LANG["ad_news_nodelete"] = "Non!";
$PMF_LANG["ad_news_delsuc"] = "L'actualité a été supprimée avec succès.";
$PMF_LANG["ad_news_updatenews"] = "Actualité mise à jour.";

// Category management
$PMF_LANG["ad_categ_new"] = "Ajouter une nouvelle catégorie";
$PMF_LANG["ad_categ_catnum"] = "Catégorie numéro";
$PMF_LANG["ad_categ_subcatnum"] = "Sous-catégorie numéro";
$PMF_LANG["ad_categ_nya"] = "<em>non disponible !</em>";
$PMF_LANG["ad_categ_titel"] = "Titre de la catégorie";
$PMF_LANG["ad_categ_add"] = "Ajouter une catégorie";
$PMF_LANG["ad_categ_existing"] = "Catégories existantes";
$PMF_LANG["ad_categ_id"] = "#";
$PMF_LANG["ad_categ_categ"] = "Catégorie";
$PMF_LANG["ad_categ_subcateg"] = "Sous catégorie";
$PMF_LANG["ad_categ_action"] = "Action";
$PMF_LANG["ad_categ_update"] = "Éditer";
$PMF_LANG["ad_categ_delete"] = "Supprimer";
$PMF_LANG["ad_categ_updatecateg"] = "Éditer catégorie";
$PMF_LANG["ad_categ_nodata"] = "Pas trouvé dans la base";
$PMF_LANG["ad_categ_remark"] = "Veuillez noter que les articles existants ne vont plus apparaître si vous supprimez la catégorie. Vous devez soit assigner une nouvelle catégorie pour ces articles ou bien les supprimer.";
$PMF_LANG["ad_categ_edit_1"] = "Éditer";
$PMF_LANG["ad_categ_edit_2"] = "Catégorie";
$PMF_LANG["ad_categ_added"] = "La catégorie a été ajoutée.";
$PMF_LANG["ad_categ_updated"] = "La catégorie a été éditée.";
$PMF_LANG["ad_categ_del_yes"] = "Oui, supprimer!";
$PMF_LANG["ad_categ_del_no"] = "Non!";
$PMF_LANG["ad_categ_deletesure"] = "Êtes-vous sûr de vouloir supprimer cette catégorie ?";
$PMF_LANG["ad_categ_deleted"] = "Catégorie supprimée.";

// Cookies
$PMF_LANG["ad_cookie_cookiesuc"] = "Le Cookie a été écrit avec <strong>succès</strong>.";
$PMF_LANG["ad_cookie_already"] = "Un cookie est déjà existant. Vous avez les options suivantes :";
$PMF_LANG["ad_cookie_again"] = "Réappliquer le cookie";
$PMF_LANG["ad_cookie_delete"] = "Supprimer le cookie";
$PMF_LANG["ad_cookie_no"] = "Il n'y a pas de cookie enregistré. Avec un cookie vous pouvez enregistrer votre entrée au script sans devoir retaper le mot de passe. Vous avez les options suivantes";
$PMF_LANG["ad_cookie_set"] = "Placer un cookie";
$PMF_LANG["ad_cookie_deleted"] = "Cookie supprimé.";

// Adminlog
$PMF_LANG["ad_adminlog"] = "AdminLog";

// Passwd
$PMF_LANG["ad_passwd_cop"] = "Modifier votre mot de passe";
$PMF_LANG["ad_passwd_old"] = "Ancien mot de passe";
$PMF_LANG["ad_passwd_new"] = "Nouveau mot de passe";
$PMF_LANG["ad_passwd_con"] = "Confirmer le mot de passe";
$PMF_LANG["ad_passwd_change"] = "Modifier le mot de passe";
$PMF_LANG["ad_passwd_suc"] = "Mot de passe modifié.";
$PMF_LANG["ad_passwd_remark"] = "<strong>ATTENTION :</strong><br>Le Cookie a été remplacé!";
$PMF_LANG["ad_passwd_fail"] = "L'ancien mot de passe <strong>doit</strong> être entré correctement et les nouveaux doivent <strong>correspondre</strong>.";

// Adduser
$PMF_LANG["ad_adus_adduser"] = "Ajouter utilisateur";
$PMF_LANG["ad_adus_name"] = "Nom";
$PMF_LANG["ad_adus_password"] = "Mot de passe";
$PMF_LANG["ad_adus_add"] = "Ajouter utilisateur";
$PMF_LANG["ad_adus_suc"] = "Utilisateur <strong>ajouté</strong>.";
$PMF_LANG["ad_adus_edit"] = "Editer le profil";
$PMF_LANG["ad_adus_dberr"] = "Erreur de base de données";
$PMF_LANG["ad_adus_exerr"] = "Le nom d'utilisateur <strong>existe déjà</strong>.";

// Sessions
$PMF_LANG["ad_sess_id"] = "ID";
$PMF_LANG["ad_sess_sid"] = "ID de session";
$PMF_LANG["ad_sess_ip"] = "IP";
$PMF_LANG["ad_sess_time"] = "Temps";
$PMF_LANG["ad_sess_pageviews"] = "Page Vues";
$PMF_LANG["ad_sess_search"] = "Rechercher";
$PMF_LANG["ad_sess_sfs"] = "Recherche de sessions";
$PMF_LANG["ad_sess_s_ip"] = "IP";
$PMF_LANG["ad_sess_s_minct"] = "actions min. :";
$PMF_LANG["ad_sess_s_date"] = "Date";
$PMF_LANG["ad_sess_s_after"] = "après";
$PMF_LANG["ad_sess_s_before"] = "avant";
$PMF_LANG["ad_sess_s_search"] = "Chercher";
$PMF_LANG["ad_sess_session"] = "Session";
$PMF_LANG["ad_sess_r"] = "Rechercher résultats pour";
$PMF_LANG["ad_sess_referer"] = "Provenance";
$PMF_LANG["ad_sess_browser"] = "Navigateur";
$PMF_LANG["ad_sess_ai_rubrik"] = "Catégorie";
$PMF_LANG["ad_sess_ai_artikel"] = "Enregs";
$PMF_LANG["ad_sess_ai_sb"] = "Mots-clés";
$PMF_LANG["ad_sess_ai_sid"] = "ID de session";
$PMF_LANG["ad_sess_back"] = "Retour";
$PMF_LANG["ad_sess_noentry"] = "Aucune entrée";

// Statistics
$PMF_LANG["ad_rs"] = "Statistiques des votes";
$PMF_LANG["ad_rs_rating_1"] = "Le classement de ";
$PMF_LANG["ad_rs_rating_2"] = "utilisateur dit :";
$PMF_LANG["ad_rs_red"] = "Rouge";
$PMF_LANG["ad_rs_green"] = "Vert";
$PMF_LANG["ad_rs_altt"] = "avec une moyenne inférieure à 20%";
$PMF_LANG["ad_rs_ahtf"] = "avec une moyenne supérieure à 80%";
$PMF_LANG["ad_rs_no"] = "Pas de classement disponible";

// Auth
$PMF_LANG["ad_auth_insert"] = "Veuillez entrer votre nom d'utilisateur et votre mot de passe.";
$PMF_LANG["ad_auth_user"] = "Utilisateur";
$PMF_LANG["ad_auth_passwd"] = "Mot de passe";
$PMF_LANG["ad_auth_ok"] = "OK";
$PMF_LANG["ad_auth_reset"] = "Réinitialiser";
$PMF_LANG["ad_auth_fail"] = "Nom d'utilisateur ou mot de passe incorrect.";
$PMF_LANG["ad_auth_sess"] = "La session ID est dépassée.";

// Added v0.8 - 24.05.2001 - Bastian - Admin
$PMF_LANG["ad_config_edit"] = "Modifier la configuration";
$PMF_LANG["ad_config_save"] = "Enregistrer la configuration";
$PMF_LANG["ad_config_reset"] = "Réinitialiser";
$PMF_LANG["ad_config_saved"] = "La configuration a bien été enregistrée.";
$PMF_LANG["ad_menu_editconfig"] = "Configuration";
$PMF_LANG["ad_att_none"] = "Pas de pièce-jointe";
$PMF_LANG["ad_att_add"] = "Joindre un fichier";
$PMF_LANG["ad_entryins_suc"] = "FAQ enregistrée.";
$PMF_LANG["ad_entryins_fail"] = "Une erreur est survenue.";
$PMF_LANG["ad_att_del"] = "Supprimer";
$PMF_LANG["ad_att_nope"] = "Les pièces-jointes ne peuvent être ajoutées qu'en édition.";
$PMF_LANG["ad_att_delsuc"] = "La pièce-jointe a été supprimée.";
$PMF_LANG["ad_att_delfail"] = "Une erreur est survenue lors de la suppression de la pièce-jointe.";
$PMF_LANG["ad_entry_add"] = "Ajouter une FAQ";

// Added v0.85 - 08.06.2001 - Bastian - Admin
$PMF_LANG["ad_csv_make"] = "Une sauvegarde est une image complète de la base de données. Le format est un script SQL qui peut être importé dans PHPMyAdmin ou en commande SQL. Une sauvegarde devrait être effectuée au moins une fois par mois.";
$PMF_LANG["ad_csv_link"] = "Télécharger la sauvegarde";
$PMF_LANG["ad_csv_head"] = "Créer une sauvegarde";
$PMF_LANG["ad_att_addto"] = "Ajouter une pièce-jointe à une FAQ";
$PMF_LANG["ad_att_addto_2"] = "";
$PMF_LANG["ad_att_att"] = "Sélectionner un fichier";
$PMF_LANG["ad_att_butt"] = "OK";
$PMF_LANG["ad_att_suc"] = "Le fichier a bien été attaché.";
$PMF_LANG["ad_att_fail"] = "Une erreur est survenue.";
$PMF_LANG["ad_att_close"] = "Fermer cette fenêtre";

// Added v0.85 - 08.07.2001 - Bastian - Admin
$PMF_LANG["ad_csv_restore"] = "Avec ce formulaire, vous pouvez restaurer le contenu de la base de données, sauvegardé avec phpMyFAQ. Veuillez noter que la base actuelle va être écrasée.";
$PMF_LANG["ad_csv_file"] = "Fichier";
$PMF_LANG["ad_csv_ok"] = "OK";
$PMF_LANG["ad_csv_linklog"] = "Sauvegarder les logs";
$PMF_LANG["ad_csv_linkdat"] = "Sauvegarder les données";
$PMF_LANG["ad_csv_head2"] = "Restauration";
$PMF_LANG["ad_csv_no"] = "Cela ne semble pas être une sauvegarde de phpMyFAQ.";
$PMF_LANG["ad_csv_prepare"] = "Préparation des requêtes de la base de données...";
$PMF_LANG["ad_csv_process"] = "interrogation...";
$PMF_LANG["ad_csv_of"] = "de";
$PMF_LANG["ad_csv_suc"] = "effectuée.";
$PMF_LANG["ad_csv_backup"] = "Sauvegarde";
$PMF_LANG["ad_csv_rest"] = "Restaurer une sauvegarde";

// Added v0.8 - 25.05.2001 - Bastian - Admin
$PMF_LANG["ad_menu_backup"] = "Sauvegarde";
$PMF_LANG["ad_logout"] = "Session terminée avec succès.";
$PMF_LANG["ad_news_add"] = "Ajouter une actualité";
$PMF_LANG["ad_news_edit"] = "Editer une actualité";
$PMF_LANG["ad_cookie"] = "Cookies";
$PMF_LANG["ad_sess_head"] = "Voir les sessions";

// Added v1.1 - 06.01.2002 - Bastian
$PMF_LANG["ad_menu_stat"] = "Statistiques des votes";
$PMF_LANG["ad_kateg_add"] = "Ajouter une catégorie";
$PMF_LANG["ad_kateg_rename"] = "Éditer";
$PMF_LANG["ad_adminlog_date"] = "Date";
$PMF_LANG["ad_adminlog_user"] = "Utilisateur";
$PMF_LANG["ad_adminlog_ip"] = "Adresse IP";

$PMF_LANG["ad_stat_sess"] = "Sessions";
$PMF_LANG["ad_stat_days"] = "Jours";
$PMF_LANG["ad_stat_vis"] = "Sessions (Visites)";
$PMF_LANG["ad_stat_vpd"] = "Visites par jour";
$PMF_LANG["ad_stat_fien"] = "Premier Log";
$PMF_LANG["ad_stat_laen"] = "Dernier Log";
$PMF_LANG["ad_stat_browse"] = "parcours Sessions";
$PMF_LANG["ad_stat_ok"] = "OK";

$PMF_LANG["ad_sess_time"] = "Heure";
$PMF_LANG["ad_sess_sid"] = "ID de Session";
$PMF_LANG["ad_sess_ip"] = "Adresse IP";

$PMF_LANG["ad_ques_take"] = "Réponde à la question";
$PMF_LANG["no_cats"] = "Pas de catégorie trouvée!";

// Added v1.1 - 17.01.2002 - Bastian
$PMF_LANG["ad_log_lger"] = "Utilisateur ou mot de passe invalide.";
$PMF_LANG["ad_log_sess"] = "Session expirée.";
$PMF_LANG["ad_log_edit"] = "Le formulaire \"Editer utilisateur\" a été appelé : ";
$PMF_LANG["ad_log_crea"] = "Le formulaire \"Nouvelle FAQ\" a été appelé.";
$PMF_LANG["ad_log_crsa"] = "Il y a une nouvelle publication.";
$PMF_LANG["ad_log_ussa"] = "Les données de l'utilisateur suivant ont étés modifiées : ";
$PMF_LANG["ad_log_usde"] = "L'utilisateur suivant a été supprimé : ";
$PMF_LANG["ad_log_beed"] = "Formulaire d'édition pour l'utilisateur suivant a été appelé : ";
$PMF_LANG["ad_log_bede"] = "La FAQ suivante a été supprimée : ";

$PMF_LANG["ad_start_visits"] = "Visites";
$PMF_LANG["ad_start_articles"] = "Articles";
$PMF_LANG["ad_start_comments"] = "Commentaires";

// Added v1.1 - 30.01.2002 - Bastian
$PMF_LANG["ad_categ_paste"] = "Coller";
$PMF_LANG["ad_categ_cut"] = "Couper";
$PMF_LANG["ad_categ_copy"] = "Copier";
$PMF_LANG["ad_categ_process"] = "Traitement des catégories...";

// Added v1.1.4 - 07.05.2002 - Thorsten
$PMF_LANG["err_NotAuth"] = "<strong>Vous n'êtes pas autorisé.</strong>";

// Added v1.2.3 - 29.11.2002 - Thorsten
$PMF_LANG["msgPreviusPage"] = "Page précédente";
$PMF_LANG["msgNextPage"] = "Page suivante";
$PMF_LANG["msgPageDoublePoint"] = "Page : ";
$PMF_LANG["msgMainCategory"] = "Catégorie principale";

// Added v1.2.4 - 30.01.2003 - Thorsten
$PMF_LANG["ad_passwdsuc"] = "Votre mot de passe a été modifié.";

// Added v1.3.0 - 04.03.2003 - Thorsten
$PMF_LANG["ad_xml_gen"] = "Créer un export XML";
$PMF_LANG["ad_entry_locale"] = "Langue";
$PMF_LANG["msgLanguageSubmit"] = "Changer la langue";

// Added v1.3.1 - 29.04.2003 - Thorsten
$PMF_LANG["ad_attach_4"] = "Le fichier attaché doit être inférieur à %s Octets.";
$PMF_LANG["ad_menu_export"] = "Exporter votre FAQ";

$PMF_LANG["rightsLanguage::add_user"] = "Ajouter des utilisateurs";
$PMF_LANG["rightsLanguage::edit_user"] = "Modifier les utilisateurs";
$PMF_LANG["rightsLanguage::delete_user"] = "Supprimer les utilisateurs";
$PMF_LANG["rightsLanguage::add_faq"] = "Ajouter des FAQs";
$PMF_LANG["rightsLanguage::edit_faq"] = "Modifier les FAQs";
$PMF_LANG["rightsLanguage::delete_faq"] = "Supprimer les FAQs";
$PMF_LANG["rightsLanguage::viewlog"] = "Voir les logs";
$PMF_LANG["rightsLanguage::adminlog"] = "Accès à l'admin log";
$PMF_LANG["rightsLanguage::delcomment"] = "Supprimer les commentaires";
$PMF_LANG["rightsLanguage::addnews"] = "Ajouter des actualités";
$PMF_LANG["rightsLanguage::editnews"] = "Modifier les actualités";
$PMF_LANG["rightsLanguage::delnews"] = "Supprimer les actualités";
$PMF_LANG["rightsLanguage::addcateg"] = "Ajouter une catégorie";
$PMF_LANG["rightsLanguage::editcateg"] = "Modifier les catégories";
$PMF_LANG["rightsLanguage::delcateg"] = "Supprimer les catégorie";
$PMF_LANG["rightsLanguage::passwd"] = "Modifier les mots de passe";
$PMF_LANG["rightsLanguage::editconfig"] = "Modifier la configuration";
$PMF_LANG["rightsLanguage::addatt"] = "Ajouter des pièces-jointes";
$PMF_LANG["rightsLanguage::delatt"] = "Supprimer les pièces-jointes";
$PMF_LANG["rightsLanguage::backup"] = "Créer une sauvegarde";
$PMF_LANG["rightsLanguage::restore"] = "Restaurer une sauvegarde";
$PMF_LANG["rightsLanguage::delquestion"] = "Supprimer les question ouverte";
$PMF_LANG["rightsLanguage::changebtrevs"] = "Modifier les révisions";

$PMF_LANG["msgAttachedFiles"] = "Pièces-jointes :";

// Added v1.3.3 - 27.05.2003 - Thorsten
$PMF_LANG["ad_user_action"] = "Action";
$PMF_LANG["ad_entry_email"] = "E-mail";
$PMF_LANG["ad_entry_allowComments"] = "Autoriser les commentaires";
$PMF_LANG["msgWriteNoComment"] = "Vous ne pouvez pas commenter cet enregistrement";
$PMF_LANG["ad_user_realname"] = "Nom réel";
$PMF_LANG["ad_export_generate_pdf"] = "Créer un fichier PDF";
$PMF_LANG["ad_export_full_faq"] = "Votre FAQ en PDF : ";
$PMF_LANG["err_bannedIP"] = "Votre adresse IP a été bannie.";
$PMF_LANG["err_SaveQuestion"] = "Les champs requis sont <strong>votre nom</strong>, <strong>votre adresser e-mail</strong>, <strong>votre question</strong> et, si requis, le code <strong><a href=\"https://fr.wikipedia.org/wiki/Captcha\" title=\"En savoir plus sur les Captcha sur Wikipedia\" target=\"_blank\">Captcha</a></strong>.";

// added v1.4.0 - 2003-12-04 by Thorsten / Mathias
$LANG_CONF["main.language"] = ["select", "Langue"];
$LANG_CONF["main.languageDetection"] = ["checkbox", "Activer la détection automatique de la langue"];
$LANG_CONF["main.titleFAQ"] = ["input", "Titre de la FAQ"];
$LANG_CONF["main.currentVersion"] = ["print", "Version de phpMyFAQ"];
$LANG_CONF["main.metaDescription"] = ["input", "Description"];
$LANG_CONF["main.metaKeywords"] = ["input", "Mots-clés pour les moteurs de recherche"];
$LANG_CONF["main.metaPublisher"] = ["input", "Nom du publicateur"];
$LANG_CONF["main.administrationMail"] = ["input", "Adresse Email de l'administrateur"];
$LANG_CONF["main.contactInformation"] = ["area", "Informations de contact"];
$LANG_CONF["main.send2friendText"] = ["area", "Texte pour la page d’envoi à un ami"];
$LANG_CONF["records.maxAttachmentSize"] = ["input", "Taille maximum des pièces-jointes en Octets (max. %s Octets)"];
$LANG_CONF["records.disableAttachments"] = ["checkbox", "Activer la visibilité des pièces-jointes"];
$LANG_CONF["main.enableUserTracking"] = ["checkbox", "Activer le tracking utilisateur"];
$LANG_CONF["main.enableAdminLog"] = ["checkbox", "Utiliser l'Adminlog ?"];
$LANG_CONF["main.enableCategoryRestrictions"] = ["checkbox", "Activer les restrictions de catégorie"];
$LANG_CONF["security.ipCheck"] = ["checkbox", "Vérifier l'IP dans l'administration"];
$LANG_CONF["records.numberOfRecordsPerPage"] = ["input", "Nombre de sujets affichés par page"];
$LANG_CONF["records.numberOfShownNewsEntries"] = ["input", "Nombre de nouveaux articles"];
$LANG_CONF["security.bannedIPs"] = ["area", "Bannir ces adresses IP"];
$LANG_CONF["main.enableRewriteRules"] = ["checkbox", "Activer le support de mod_rewrite? (défaut : désactivé)"];
$LANG_CONF["ldap.ldapSupport"] = ["checkbox", "Activer le support de LDAP (défaut : désactivé)"];
$LANG_CONF["main.referenceURL"] = ["input", "URL de votre FAQ (e.g. : http://www.example.org/faq/)"];
$LANG_CONF["main.urlValidateInterval"] = ["input", "Intervalle entre la vérification des liens AJAX (en secondes)"];
$LANG_CONF["records.enableVisibilityQuestions"] = ["checkbox", "Désactiver la visibilité de nouvelles questions ?"];
$LANG_CONF["security.permLevel"] = ["select", "Niveau d'autorisation"];

$PMF_LANG["ad_categ_new_main_cat"] = "comme catégorie principale";
$PMF_LANG["ad_categ_paste_error"] = "Déplacer cette catégorie est impossible.";
$PMF_LANG["ad_categ_move"] = "Déplacer la catégorie";
$PMF_LANG["ad_categ_lang"] = "Langue";
$PMF_LANG["ad_categ_desc"] = "Description";
$PMF_LANG["ad_categ_change"] = "Changer avec";

$PMF_LANG["lostPassword"] = "Mot de passe oublié ?";
$PMF_LANG["lostpwd_err_1"] = "Erreur : Nom d'utilisateur et adresse e-mail inconnus.";
$PMF_LANG["lostpwd_err_2"] = "Erreur : Mauvaise saisie !";
$PMF_LANG["lostpwd_text_1"] = "Merci d'avoir demandé des informations sur votre compte.";
$PMF_LANG["lostpwd_text_2"] = "Merci de changer votre mot de passe personnel dans la section d'administration de votre FAQ.";
$PMF_LANG["lostpwd_mail_okay"] = "L'e-mail a été envoyé.";

$PMF_LANG["ad_xmlrpc_button"] = "Cliquez pour vérifier la version de votre installation phpMyFAQ";
$PMF_LANG["ad_xmlrpc_latest"] = "Dernière version disponible sur";

// added v1.5.0 - 2005-07-31 by Thorsten
$PMF_LANG["ad_categ_select"] = "Sélectionner la langue de la catégorie";

// added v1.5.1 - 2005-09-06 by Thorsten
$PMF_LANG["msgSitemap"] = "Plan du site";

// added v1.5.2 - 2005-09-23 by Lars
$PMF_LANG["err_inactiveArticle"] = "Accès non autorisé";
$PMF_LANG["msgArticleCategories"] = "Catégories de cet article";

// added v1.6.0 - 2006-02-02 by Thorsten
$PMF_LANG["ad_entry_solution_id"] = "ID unique de l'article ";
$PMF_LANG["ad_entry_faq_record"] = "Enregistrement FAQ";
$PMF_LANG["ad_entry_new_revision"] = "Créer une nouvelle révision ?";
$PMF_LANG["ad_entry_record_administration"] = "Publication";
$PMF_LANG["ad_entry_revision"] = "Révision";
$PMF_LANG["ad_changerev"] = "Sélectionner une révision";
$PMF_LANG["msgCaptcha"] = "Merci de saisir le code Captcha";
$PMF_LANG["msgSelectCategories"] = "Rechercher dans...";
$PMF_LANG["msgAllCategories"] = "... toutes les catégories";
$PMF_LANG["ad_you_should_update"] = "Votre version de phpMyFAQ est obsolète. Vous devriez la mettre à jour vers la dernière version disponible.";
$PMF_LANG["msgAdvancedSearch"] = "Recherche avancée";

// added v1.6.1 - 2006-04-25 by Matteo and Thorsten
$PMF_LANG["spamControlCenter"] = "Centre de contrôle Spam";
$LANG_CONF["spam.enableSafeEmail"] = ["checkbox", "Affichage sécurisé de l'e-mail utilisateur."];
$LANG_CONF["spam.checkBannedWords"] = ["checkbox", "Analyser le contenu des formulaires publics pour éviter les termes prohibés."];
$LANG_CONF["spam.enableCaptchaCode"] = ["checkbox", "Utiliser un code Captcha pour autoriser la soumission d'un formulaire public."];
$PMF_LANG["ad_session_expiring"] = "Votre session va expirer dans %d minutes : Souhaitez-vous continuer à travailler ?";

// added v1.6.2 - 2006-06-13 by Matteo
$PMF_LANG["ad_stat_management"] = "Gestion des sessions";
$PMF_LANG["ad_stat_choose"] = "Choisir le mois";
$PMF_LANG["ad_stat_delete"] = "Effacer immédiatement la session sélectionnée ?";

// added v2.0.0 - 2005-09-15 by Thorsten and by Minoru TODA
$PMF_LANG["ad_menu_glossary"] = "Glossaire";
$PMF_LANG["ad_glossary_add"] = "Ajouter une entrée au glossaire";
$PMF_LANG["ad_glossary_edit"] = "Modifier l'entrée du glossaire";
$PMF_LANG["ad_glossary_item"] = "Titre";
$PMF_LANG["ad_glossary_definition"] = "Définition";
$PMF_LANG["ad_glossary_save"] = "Enregistrer l'entrée";
$PMF_LANG["ad_glossary_save_success"] = "L'entrée du glossaire à été enregistrée avec succès !";
$PMF_LANG["ad_glossary_save_error"] = "Une erreur est survenue pendant l'enregistrement !";
$PMF_LANG["ad_glossary_update_success"] = "L'entrée du glossaire à été mise à jour avec succès !";
$PMF_LANG["ad_glossary_update_error"] = "Une erreur est survenue pendant la mise à jour !";
$PMF_LANG["ad_glossary_delete"] = "Supprimer l'entrée";
$PMF_LANG["ad_glossary_delete_success"] = "L'entrée du glossaire à été supprimée avec succès !";
$PMF_LANG["ad_glossary_delete_error"] = "Une erreur est survenue pendant la suppression !";
$PMF_LANG["msgNewQuestionVisible"] = "La question doit d'abord être examinée avant de devenir public.";
$PMF_LANG["msgQuestionsWaiting"] = "En attente de publication par les administrateurs :";
$PMF_LANG["ad_entry_visibility"] = "Publié";
$PMF_LANG["ad_entry_not_visibility"] = "Non publié";

// added v2.0.0 - 2006-01-02 by Lars
$PMF_LANG["ad_user_error_password"] = "Veuillez entrer un mot de passe.";
$PMF_LANG["ad_user_error_passwordsDontMatch"] = "Les mots de passe ne correspondent pas.";
$PMF_LANG["ad_user_error_loginInvalid"] = "Le nom d'utilisateur spécifié n'est pas valide.";
$PMF_LANG["ad_user_error_noEmail"] = "Entrez, s'il vous plaît, votre adresse email. ";
$PMF_LANG["ad_user_error_noRealName"] = "Entrez, s'il vous plaît, votre vrai nom. ";
$PMF_LANG["ad_user_error_delete"] = "Ce compte utilisateur ne peut être supprimé. ";
$PMF_LANG["ad_user_error_noId"] = "Pas d' ID spécifié. ";
$PMF_LANG["ad_user_error_protectedAccount"] = "Compte d'utilisateur protégé. ";
$PMF_LANG["ad_user_deleteUser"] = "Effacer un utilisateur";
$PMF_LANG["ad_user_status"] = "Statut";
$PMF_LANG["ad_user_lastModified"] = "Dernière modification";
$PMF_LANG["ad_gen_cancel"] = "Annuler";
$PMF_LANG["rightsLanguage::addglossary"] = "Ajouter un glossaire";
$PMF_LANG["rightsLanguage::editglossary"] = "Modifier un glossaire";
$PMF_LANG["rightsLanguage::delglossary"] = "Supprimer un glossaire";
$PMF_LANG["ad_menu_group_administration"] = "Groupes";
$PMF_LANG["ad_user_loggedin"] = "Connecté en tant que";
$PMF_LANG["ad_group_details"] = "Détails du groupe";
$PMF_LANG["ad_group_add"] = "Ajouter un groupe";
$PMF_LANG["ad_group_add_link"] = "Ajouter un groupe";
$PMF_LANG["ad_group_name"] = "Nom :";
$PMF_LANG["ad_group_description"] = "Description :";
$PMF_LANG["ad_group_autoJoin"] = "Rejoindre automatiquement :";
$PMF_LANG["ad_group_suc"] = "Groupe ajouté <strong>avec succès</strong>.";
$PMF_LANG["ad_group_error_noName"] = "Merci de saisir un nom de groupe. ";
$PMF_LANG["ad_group_error_delete"] = "Le groupe ne peut être supprimé. ";
$PMF_LANG["ad_group_deleted"] = "Le groupe a été supprimé avec succès.";
$PMF_LANG["ad_group_deleteGroup"] = "Effacer le groupe";
$PMF_LANG["ad_group_deleteQuestion"] = "Êtes-vous sûr(e) de vouloir supprimer ce groupe ?";
$PMF_LANG["ad_user_uncheckall"] = "Tout déselectionner";
$PMF_LANG["ad_group_membership"] = "Membres du groupe";
$PMF_LANG["ad_group_members"] = "Membres";
$PMF_LANG["ad_group_addMember"] = "+";
$PMF_LANG["ad_group_removeMember"] = "-";

// added v2.0.0 - 2006-07-20 by Matteo
$PMF_LANG["ad_export_which_cat"] = "Limiter les données de la FAQ sui peuvent être exportées (optionnel)";
$PMF_LANG["ad_export_cat_downwards"] = "Inclure les catégories enfant";
$PMF_LANG["ad_export_type"] = "Format de l'export";
$PMF_LANG["ad_export_type_choose"] = "Formats supportés : ";
$PMF_LANG["ad_export_download_view"] = "Télécharger ou visualiser en ligne ?";
$PMF_LANG["ad_export_download"] = "Télécharger";
$PMF_LANG["ad_export_view"] = "Visualiser en ligne";
$PMF_LANG["ad_export_gen_xhtml"] = "Créer un fichier XHTML";

// added v2.0.0 - 2006-07-22 by Matteo
$PMF_LANG["ad_news_data"] = "Contenu de l'actualité";
$PMF_LANG["ad_news_author_name"] = "Nom de l'auteur :";
$PMF_LANG["ad_news_author_email"] = "Email de l'auteur :";
$PMF_LANG["ad_news_set_active"] = "Activer";
$PMF_LANG["ad_news_allowComments"] = "Autoriser les commentaires :";
$PMF_LANG["ad_news_expiration_window"] = "Définir un intervalle de temps pour cette actualité (optionnel)";
$PMF_LANG["ad_news_from"] = "De :";
$PMF_LANG["ad_news_to"] = "A :";
$PMF_LANG["ad_news_insertfail"] = "Une erreur a eu lieu lors de l'insertion de l'actualité dans la base de données.";
$PMF_LANG["ad_news_updatefail"] = "Une erreur a eu lieu lors de la mise à jour de l'actualité dans la base de données";
$PMF_LANG["newsShowCurrent"] = "Montrer les actualités.";
$PMF_LANG["newsShowArchive"] = "Montrer les actualités archivées.";
$PMF_LANG["newsArchive"] = " Archives des actualités";
$PMF_LANG["newsWriteComment"] = "Commenter cette actualité";
$PMF_LANG["newsCommentDate"] = "Ajoutée à : ";

// added v2.0.0 - 2006-07-29 by Matteo & Thorsten
$PMF_LANG["ad_record_expiration_window"] = "Définir un intervalle de temps pour cet enregistrement (optionnel)";
$PMF_LANG["admin_mainmenu_home"] = "Tableau de bord";
$PMF_LANG["admin_mainmenu_users"] = "Utilisateurs";
$PMF_LANG["admin_mainmenu_content"] = "Contenu";
$PMF_LANG["admin_mainmenu_statistics"] = "Statistiques";
$PMF_LANG["admin_mainmenu_exports"] = "Exports";
$PMF_LANG["admin_mainmenu_backup"] = "Sauvegardes";
$PMF_LANG["admin_mainmenu_configuration"] = "Configuration";
$PMF_LANG["admin_mainmenu_logout"] = "Déconnexion";

// added v2.0.0 - 2006-08-15 by Thorsten and Matteo
$PMF_LANG["ad_categ_owner"] = "Propriétaire de la catégorie";
$PMF_LANG["adminSection"] = "Administration";
$PMF_LANG["err_expiredArticle"] = "Cette entrée a expiré et ne peut plus être affichée";
$PMF_LANG["err_expiredNews"] = "Cette news a expiré et ne peut plus être affichée";
$PMF_LANG["err_inactiveNews"] = "Cette news est en cours de révision et ne peut pas être affichée";
$PMF_LANG["msgSearchOnAllLanguages"] = "Chercher dans toutes les langues";
$PMF_LANG["ad_entry_tags"] = "Tags ";
$PMF_LANG["msg_tags"] = "Tags";

// added v2.0.0 - 2006-09-02 by Thomas
$PMF_LANG["msg_related_articles"] = "FAQs associées";
$LANG_CONF["records.numberOfRelatedArticles"] = ["input", "Nombre de FAQs associées"];

// added v2.0.0 - 2006-09-09 by Rudi
$PMF_LANG["ad_categ_trans_1"] = "Traduire";
$PMF_LANG["ad_categ_trans_2"] = "Catégorie";
$PMF_LANG["ad_categ_translatecateg"] = "Traduire la catégorie";
$PMF_LANG["ad_categ_translate"] = "Traduire";
$PMF_LANG["ad_categ_transalready"] = "Déjà traduit en :";
$PMF_LANG["ad_categ_deletealllang"] = "Supprimer dans toutes les langues?";
$PMF_LANG["ad_categ_deletethislang"] = "Supprimer seulement dans cette langue?";
$PMF_LANG["ad_categ_translated"] = "La catégorie a été traduite.";

// added v2.0.0 - 2006-09-21 by Rudi
$PMF_LANG["ad_categ_show"] = "Vue d'ensemble";
$PMF_LANG["ad_menu_categ_structure"] = "Vue d'ensemble des catégories incluant leurs langues";

// added v2.0.0 - 2006-09-26 by Thorsten
$PMF_LANG["ad_entry_userpermission"] = "Permissions des utilisateurs :";
$PMF_LANG["ad_entry_grouppermission"] = "Permissions des groupes :";
$PMF_LANG["ad_entry_all_users"] = "Accessible à tous les utilisateurs";
$PMF_LANG["ad_entry_restricted_users"] = "Accès restreint à";
$PMF_LANG["ad_entry_all_groups"] = "Accessible à tous les groupes";
$PMF_LANG["ad_entry_restricted_groups"] = "Accès restreint à";
$PMF_LANG["ad_session_expiration"] = "La session expire dans";
$PMF_LANG["ad_user_active"] = "Actif";
$PMF_LANG["ad_user_blocked"] = "Bloqué";
$PMF_LANG["ad_user_protected"] = "Protégé";

// added v2.0.0 - 2006-10-07 by Matteo
$PMF_LANG["ad_entry_intlink"] = "Sélectionnez une FAQ à insérer comme un lien...";

//added 2.0.0 - 2006-10-10 by Rudi
$PMF_LANG["ad_categ_paste2"] = "Coller après";
$PMF_LANG["ad_categ_remark_move"] = "L'échange de 2 catégories est seulement possible au même niveau.";
$PMF_LANG["ad_categ_remark_overview"] = "Le bon ordre des catégories sera affiché si toutes les catégories sont définies pour la même langue (première colonne).";

// added v2.0.0 - 2006-10-15 by Matteo
$PMF_LANG["msgUsersOnline"] = "%d Invités et %d Inscrits";
$PMF_LANG["ad_adminlog_del_older_30d"] = "Supprimer les enregistrements de plus de 30 jours";
$PMF_LANG["ad_adminlog_delete_success"] = "Les enregistrements les plus anciens ont été supprimés avec succès.";
$PMF_LANG["ad_adminlog_delete_failure"] = "Aucun enregistrement effacé : une erreur est survenue lors de l'opération.";

// added 2.0.0 - 2006-11-19 by Thorsten
$PMF_LANG["ad_quicklinks"] = "Liens rapides";
$PMF_LANG["ad_quick_category"] = "Ajouter une nouvelle catégorie";
$PMF_LANG["ad_quick_record"] = "Ajouter une nouvelle FAQ";
$PMF_LANG["ad_quick_user"] = "Ajouter un nouvel utilisateur";
$PMF_LANG["ad_quick_group"] = "Ajouter un nouveau groupe";

// added v2.0.0 - 2006-12-30 by Matteo
$PMF_LANG["msgNewTranslationHeader"] = "Proposition de traduction";
$PMF_LANG["msgNewTranslationAddon"] = "Votre proposition ne sera pas publiée immédiatement mais devra être approuvée par un administrateur. Les champs requis sont <strong>votre nom</strong>, <strong>votre adresse e-mail</strong>, <strong>votre traduction du titre</strong> et <strong>votre traduction de la FAQ</strong>. Merci de séparer les mots clés uniquement avec des virgules.";
$PMF_LANG["msgNewTransSourcePane"] = "Volet source";
$PMF_LANG["msgNewTranslationPane"] = "Volet traduction";
$PMF_LANG["msgNewTranslationName"] = "Votre nom :";
$PMF_LANG["msgNewTranslationMail"] = "Votre adresse email :";
$PMF_LANG["msgNewTranslationKeywords"] = "Mots clés :";
$PMF_LANG["msgNewTranslationSubmit"] = "Soumettre votre proposition";
$PMF_LANG["msgTranslate"] = "Traduire cette FAQ";
$PMF_LANG["msgTranslateSubmit"] = "Commencer la traduction...";
$PMF_LANG["msgNewTranslationThanks"] = "Merci pour votre proposition de traduction !";

// added v2.0.0 - 2007-02-27 by Matteo
$PMF_LANG["rightsLanguage::addgroup"] = "Ajouter un groupe utilisateur";
$PMF_LANG["rightsLanguage::editgroup"] = "Modifier un groupe utilisateur";
$PMF_LANG["rightsLanguage::delgroup"] = "Supprimer un groupe utilisateur";

// added v2.0.0 - 2007-02-27 by Thorsten
$PMF_LANG["ad_news_link_parent"] = "Lien dans la fenêtre parent";

// added v2.0.0 - 2007-03-04 by Thorsten
$PMF_LANG["ad_menu_comments"] = "Commentaires";
$PMF_LANG["ad_comment_administration"] = "Administration des commentaires";
$PMF_LANG["ad_comment_faqs"] = "Commentaires dans la FAQ :";
$PMF_LANG["ad_comment_news"] = "Commentaires dans les nouveaux enregistrements :";
$PMF_LANG["msgPDF"] = "Version PDF";
$PMF_LANG["ad_groups"] = "Groupes";

// added v2.0.0 - 2007-03-10 by Thorsten
$LANG_CONF["records.orderby"] = ["select", "Trier les enregistrements par"];
$LANG_CONF["records.sortby"] = ["select", "Type de Tri (descendant ou ascendant)"];
$PMF_LANG["ad_conf_order_id"] = "ID<br/>(défaut)";
$PMF_LANG["ad_conf_order_thema"] = "Titre";
$PMF_LANG["ad_conf_order_visits"] = "Nombre de visites";
$PMF_LANG["ad_conf_order_updated"] = "Date";
$PMF_LANG["ad_conf_order_author"] = "Auteur";
$PMF_LANG["ad_conf_desc"] = "descendant";
$PMF_LANG["ad_conf_asc"] = "ascendant";
$PMF_LANG["mainControlCenter"] = "Principal";
$PMF_LANG["recordsControlCenter"] = "FAQs";

// added v2.0.0 - 2007-03-29 by Thorsten
$LANG_CONF["records.defaultActivation"] = ["checkbox", "Activer les nouveaux enregistrements"];
$LANG_CONF["records.defaultAllowComments"] = ["checkbox", "Autoriser les commentaires pour les nouveaux enregistrement<br/>(défaut : désactivé)"];

// added v2.0.0 - 2007-04-04 by Thorsten
$PMF_LANG["msgAllCatArticles"] = "Enregistrements dans cette catégorie";
$PMF_LANG["msgTagSearch"] = "Entrées taggées";
$PMF_LANG["ad_pmf_info"] = "Informations phpMyFAQ";
$PMF_LANG["ad_online_info"] = "Vérification en ligne de la version";
$PMF_LANG["ad_system_info"] = "Information système";

// added 2.5.0-alpha - 2008-01-25 by Elger
$PMF_LANG["msgRegisterUser"] = "Inscription";
$PMF_LANG["ad_user_loginname"] = "Nom d'utilisateur :";
$PMF_LANG["errorRegistration"] = "Ce champ est obligatoire !";
$PMF_LANG["submitRegister"] = "Inscrire";
$PMF_LANG["msgUserData"] = "Information requise pour l'inscription";
$PMF_LANG["captchaError"] = "Veuillez entrer le code Captcha correct !";
$PMF_LANG["msgRegError"] = "Les erreurs suivantes se sont produites. Veuillez les corriger :";
$PMF_LANG["successMessage"] = "Votre inscription a réussi. Vous allez bientôt recevoir un email de confirmation avec vos données de connexion !";
$PMF_LANG["msgRegThankYou"] = "Nous vous remercions pour votre inscription";
$PMF_LANG["emailRegSubject"] = "Inscription [%sitename%] : nouvel utilisateur";

// added 2.5.0-alpha2 - 2009-01-24 by Thorsten
$PMF_LANG["msgMostPopularSearches"] = "Les recherches les plus populaires sont :";
$LANG_CONF["main.enableWysiwygEditor"] = ["checkbox", "Activer l'éditeur WYSIWYG"];


// added 2.5.0-beta - 2009-03-30 by Anatoliy
$PMF_LANG["ad_menu_searchstats"] = "Stats sur les recherches";
$PMF_LANG["ad_searchstats_search_term"] = "Mots-clés";
$PMF_LANG["ad_searchstats_search_term_count"] = "Nombre";
$PMF_LANG["ad_searchstats_search_term_lang"] = "Langue";
$PMF_LANG["ad_searchstats_search_term_percentage"] = "Pourcentage";

// added 2.5.0-beta - 2009-03-31 by Anatoliy
$PMF_LANG["ad_record_sticky"] = "Epingler";
$PMF_LANG["ad_entry_sticky"] = "Epingler";
$PMF_LANG["stickyRecordsHeader"] = "FAQs épinglées";

// added 2.5.0-beta - 2009-04-01 by Anatoliy
$PMF_LANG["ad_menu_stopwordsconfig"] = "Mots vides";
$PMF_LANG["ad_config_stopword_input"] = "Ajouter un nouveau mot vide";

// added 2.5.0-beta - 2009-04-06 by Anatoliy
$PMF_LANG["msgSendMailDespiteEverything"] = "Non, il n'y a toujours pas de réponse adéquate (envoie d'un email)";
$PMF_LANG["msgSendMailIfNothingIsFound"] = "Est-ce que la réponse recherchée se trouve dans les résultats énumérés ci-dessus ?";

// added 2.5.0-RC - 2009-05-11 by Anatoliy & Thorsten
$PMF_LANG["msgChooseLanguageToTranslate"] = "Veuillez choisir la langue de traduction";
$PMF_LANG["msgLangDirIsntWritable"] = "Le répertoire <strong>/lang</strong> des fichiers de langue n'est pas modifiable";
$PMF_LANG["ad_menu_translations"] = "Traduction de l'interface";
$PMF_LANG["ad_start_notactive"] = "Attente d'activation";

// added 2.5.0-RC - 2009-05-20 by Anatoliy
$PMF_LANG["msgTransToolAddNewTranslation"] = "Ajouter une nouvelle traduction";
$PMF_LANG["msgTransToolLanguage"] = "Langue";
$PMF_LANG["msgTransToolActions"] = "Actions";
$PMF_LANG["msgTransToolWritable"] = "Modifiable";
$PMF_LANG["msgEdit"] = "Editer";
$PMF_LANG["msgDelete"] = "Effacer";
$PMF_LANG["msgYes"] = "oui";
$PMF_LANG["msgNo"] = "non";
$PMF_LANG["msgTransToolSureDeleteFile"] = "Êtes-vous sûr(e) de vouloir supprimer ce fichier de langue ?";
$PMF_LANG["msgTransToolFileRemoved"] = "Fichiers de langue supprimé avec succès";
$PMF_LANG["msgTransToolErrorRemovingFile"] = "Erreur en supprimant ce fichier de langue";
$PMF_LANG["msgVariable"] = "Variable";
$PMF_LANG["msgCancel"] = "Annuler";
$PMF_LANG["msgSave"] = "Sauvegarder";
$PMF_LANG["msgSaving3Dots"] = "enregistrement en cours ...";
$PMF_LANG["msgRemoving3Dots"] = "suppression en cours ...";
$PMF_LANG["msgTransToolFileSaved"] = "Fichier de langue enregistré avec succès";
$PMF_LANG["msgTransToolErrorSavingFile"] = "Erreur lors de l'enregistrement de ce fichier de langue";
$PMF_LANG["msgLanguage"] = "Langues";
$PMF_LANG["msgTransToolLanguageCharset"] = "Encodage du fichier de langue";
$PMF_LANG["msgTransToolLanguageDir"] = "Sens de lecture de la langue";
$PMF_LANG["msgTransToolLanguageDesc"] = "Description de la langue";
$PMF_LANG["msgAuthor"] = "Author";
$PMF_LANG["msgTransToolAddAuthor"] = "Ajout d'un auteur";
$PMF_LANG["msgTransToolCreateTranslation"] = "Créer une traduction";
$PMF_LANG["msgTransToolTransCreated"] = "Nouvelle traduction créée avec succès";
$PMF_LANG["msgTransToolCouldntCreateTrans"] = "Impossible de créer cette nouvelle traduction";
$PMF_LANG["msgAdding3Dots"] = "ajout...";
$PMF_LANG["msgTransToolSendToTeam"] = "Envoyer à l'équipe phpMyFAQ";
$PMF_LANG["msgSending3Dots"] = "envoi...";
$PMF_LANG["msgTransToolFileSent"] = "Fichier de langue envoyé avec succès à phpMyFAQ. Merci beaucoup !";
$PMF_LANG["msgTransToolErrorSendingFile"] = "Une erreur est apparue lors de l'envoi";
$PMF_LANG["msgTransToolPercent"] = "Pourcentage";

// added 2.5.0-RC3 - 2009-06-23 by Anatoliy
$LANG_CONF["records.attachmentsPath"] = ["input", "Chemin où les pièces-jointes seront enregistrées.<br><small>Un chemin relatif est un dossier sans la racine du site</small>"];

// added 2.5.0-RC3 - 2009-06-24 by Anatoliy
$PMF_LANG["msgAttachmentNotFound"] = "Le fichier que vous essayez de télécharger n'a pas été trouvé sur le serveur.";

// added 2.6.0-alpha - 2009-07-30 by Aurimas Fišeras
//P.S. "One User online" is also possible, since sprintf just ignores extra args
$PMF_LANG["plmsgUserOnline"]["0"] = "%d utilisateur en ligne";
$PMF_LANG["plmsgUserOnline"]["1"] = "%d utilisateurs en ligne";
// added 2.6.0-alpha - 2009-08-02 by Anatoliy
$LANG_CONF["main.templateSet"] = ["select", "Template à utiliser"];

// added 2.6.0-alpha - 2009-08-16 by Aurimas Fišeras
$PMF_LANG["msgTransToolRemove"] = "Supprimer";
$PMF_LANG["msgTransToolLanguageNumberOfPlurals"] = "Nombre de forme de pluriels";
$PMF_LANG["msgTransToolLanguageOnePlural"] = "Cette langue a seulement une forme de pluriel";
$PMF_LANG["msgTransToolLanguagePluralNotSet"] = "Le support du pluriel pour la langue %s est désactivé (nplurals non activé)";

// added 2.6.0-alpha - 2009-08-16 by Aurimas Fišeras - Plural messages
$PMF_LANG["plmsgHomeArticlesOnline"]["0"] = "Il y a %d FAQ en ligne";
$PMF_LANG["plmsgHomeArticlesOnline"]["1"] = "Il y a %d FAQs en ligne";
$PMF_LANG["plmsgViews"]["0"] = "%d affichage";
$PMF_LANG["plmsgViews"]["1"] = "%d affichages";

// added 2.6.0-alpha - 2009-08-30 by Aurimas Fišeras - Plural messages
$PMF_LANG["plmsgGuestOnline"]["0"] = "%d invité";
$PMF_LANG["plmsgGuestOnline"]["1"] = "%d invités";
$PMF_LANG["plmsgRegisteredOnline"]["0"] = " et %d membre";
$PMF_LANG["plmsgRegisteredOnline"]["1"] = " et %d membres";
$PMF_LANG["plmsgSearchAmount"]["0"] = "%d résultat de recherche";
$PMF_LANG["plmsgSearchAmount"]["1"] = "%d résultats de recherche";
$PMF_LANG["plmsgPagesTotal"]["0"] = " %d Page";
$PMF_LANG["plmsgPagesTotal"]["1"] = " %d Pages";
$PMF_LANG["plmsgVotes"]["0"] = "%d Vote";
$PMF_LANG["plmsgVotes"]["1"] = "%d Votes";
$PMF_LANG["plmsgEntries"]["0"] = "%d FAQ";
$PMF_LANG["plmsgEntries"]["1"] = "%d FAQs";

// added 2.6.0-alpha - 2009-09-06 by Aurimas Fišeras
$PMF_LANG["rightsLanguage::addtranslation"] = "Ajouter une traduction";
$PMF_LANG["rightsLanguage::edittranslation"] = "Editer une traduction";
$PMF_LANG["rightsLanguage::deltranslation"] = "Supprimer une traduction";
$PMF_LANG["rightsLanguage::approverec"] = "Approuver l'enregistrement";

// added 2.6.0-alpha - 2009-09-9 by Anatoliy Belsky
$LANG_CONF["records.enableAttachmentEncryption"] = ["checkbox", "Activer les pièces-jointes cryptées<br/><small>Ignoré si les pièces-jointes sont désactivés</small>"];
$LANG_CONF["records.defaultAttachmentEncKey"] = ["input", "Clé de cryptage par défaut<br/><small>Ignoré si le cryptage des pièces-jointes est désactivé</small><br/><small><font color='red'>ATTENTION : Ne pas modifier une fois que le cryptage des fichiers a été activé !!!</font></small>"];

// added 2.6.0-alpha - 2009-09-06 by Thorsten
$PMF_LANG["ad_menu_upgrade"] = "Mise à jour";
$PMF_LANG["ad_you_shouldnt_update"] = "Vous avez la dernière version de phpMyFAQ. Inutile de faire une mise à jour.";
$LANG_CONF["security.useSslForLogins"] = ["checkbox", "Autoriser uniquement les connexions à travers une connexion SSL ?"];
$PMF_LANG["msgSecureSwitch"] = "Passez en mode sécurisé pour la connexion !";

// added 2.6.0-alpha - 2009-10-03 by Anatoliy Belsky
$PMF_LANG["msgTransToolNoteFileSaving"] = "Merci de noter qu'aucun fichier ne sera enregistré jusqu'au clic sur le bouton sauvegarde";
$PMF_LANG["msgTransToolPageBufferRecorded"] = "Page %d buffer enregitrée avec succès";
$PMF_LANG["msgTransToolErrorRecordingPageBuffer"] = "Erreur lors de l'enregistrement de la page %d tampon";
$PMF_LANG["msgTransToolRecordingPageBuffer"] = "Enregistrement de la page %d tampon";

// added 2.6.0-alpha - 2009-11-02 by Anatoliy Belsky
$PMF_LANG["ad_record_active"] = "Active";

// added 2.6.0-alpha - 2009-11-01 by Anatoliy Belsky
$PMF_LANG["msgAttachmentInvalid"] = "La pièce-jointe est invalide, merci d'en informer l'administrateur";

// added 2.6.0-alpha - 2009-11-02 by max
$LANG_CONF["search.numberSearchTerms"] = ["input", "Nombre de termes de recherche répertoriés"];
$LANG_CONF["records.orderingPopularFaqs"] = ["select", "Tri des FAQ les plus populaires"];
$PMF_LANG["list_all_users"] = "Lister tous les utilisateurs";

$PMF_LANG["records.orderingPopularFaqs.visits"] = "lister par FAQ les plus consultées";
$PMF_LANG["records.orderingPopularFaqs.voting"] = "lister par FAQ les mieux notées";

// added 2.6.0-alpha - 2009-11-05 by Thorsten
$PMF_LANG["msgShowHelp"] = "Merci de séparer les mots avec une virgule.";

// added 2.6.0-RC - 2009-11-30 by Thorsten
$PMF_LANG["msgUpdateFaqDate"] = "Mettre à jour";
$PMF_LANG["msgKeepFaqDate"] = "Conserver";
$PMF_LANG["msgEditFaqDat"] = "Editer";
$LANG_CONF["main.optionalMailAddress"] = ["checkbox", "Adresse email comme champ obligatoire"];

// added v2.6.99 - 2010-11-24 by Gustavo Solt
$LANG_CONF["search.relevance"] = ["select", "Tri par pertinence"];
$LANG_CONF["search.enableRelevance"] = ["checkbox", "Activer le support de la pertinence ?"];
$PMF_LANG["searchControlCenter"] = "Recherche";
$PMF_LANG["search.relevance.thema-content-keywords"] = "Question - Réponse - Mots-clés";
$PMF_LANG["search.relevance.thema-keywords-content"] = "Question - Mots-clés - Réponse";
$PMF_LANG["search.relevance.content-thema-keywords"] = "Réponse - Question - Mots-clés";
$PMF_LANG["search.relevance.content-keywords-thema"] = "Réponse - Mots-clés - Question";
$PMF_LANG["search.relevance.keywords-content-thema"] = "Mots-clés - Réponse - Question";
$PMF_LANG["search.relevance.keywords-thema-content"] = "Mots-clés - Question - Réponse";

// added 2.7.0-alpha - 2010-09-13 by Thorsten
$PMF_LANG["msgLoginUser"] = "Connexion";
$PMF_LANG["socialNetworksControlCenter"] = "Réseaux sociaux";
$LANG_CONF["socialnetworks.enableTwitterSupport"] = ["checkbox", "Support Twitter"];
$LANG_CONF["socialnetworks.twitterConsumerKey"] = ["input", "Twitter Consumer Key"];
$LANG_CONF["socialnetworks.twitterConsumerSecret"] = ["input", "Twitter Consumer Secret"];

// added 2.7.0-alpha - 2010-10-14 by Tom Zeithaml
$LANG_CONF["socialnetworks.twitterAccessTokenKey"] = ["input", "Twitter Access Token Key"];
$LANG_CONF["socialnetworks.twitterAccessTokenSecret"] = ["input", "Twitter Access Token Secret"];

// added 2.7.0-alpha - 2010-12-21 by Anatoliy Belsky
$PMF_LANG["ad_menu_attachments"] = "Pièces-jointes";
$PMF_LANG["ad_menu_attachment_admin"] = "Administration des pièces-jointes";
$PMF_LANG["msgAttachmentsFilename"] = "Nom de fichier";
$PMF_LANG["msgAttachmentsFilesize"] = "Taille du fichier";
$PMF_LANG["msgAttachmentsMimeType"] = "Type MIME";
$PMF_LANG["msgAttachmentsWannaDelete"] = "Êtes-vous sûr de vouloir supprimer cette pièce-jointe ?";
$PMF_LANG["msgAttachmentsDeleted"] = "Pièce-jointe supprimée avec <strong>succès</strong>.";

// added v2.7.0-alpha2 - 2010-01-12 by Gustavo Solt
$PMF_LANG["ad_menu_reports"] = "Rapports";
$PMF_LANG["ad_stat_report_fields"] = "Champs";
$PMF_LANG["ad_stat_report_category"] = "Catégorie";
$PMF_LANG["ad_stat_report_sub_category"] = "Sous-catégorie";
$PMF_LANG["ad_stat_report_translations"] = "Traductions";
$PMF_LANG["ad_stat_report_language"] = "Langue";
$PMF_LANG["ad_stat_report_id"] = "ID FAQ";
$PMF_LANG["ad_stat_report_sticky"] = "FAQ épinglée";
$PMF_LANG["ad_stat_report_title"] = "Question";
$PMF_LANG["ad_stat_report_creation_date"] = "Date";
$PMF_LANG["ad_stat_report_owner"] = "Auteur original";
$PMF_LANG["ad_stat_report_last_modified_person"] = "Dernier auteur";
$PMF_LANG["ad_stat_report_url"] = "URL";
$PMF_LANG["ad_stat_report_visits"] = "Visites";
$PMF_LANG["ad_stat_report_make_report"] = "Générer un rapport";
$PMF_LANG["ad_stat_report_make_csv"] = "Exporter en CSV";

// added v2.7.0-alpha2 - 2010-02-05 by Thorsten Rinne
$PMF_LANG["msgRegistration"] = "Inscription";
$PMF_LANG["msgRegistrationCredentials"] = "Pour vous inscrire, veuillez entrer votre nom, votre login et une adresse email valide !";
$PMF_LANG["msgRegistrationNote"] = "Après avoir soumis ce formulaire, vous recevrez un e-mail après que l'administrateur ait autorisé votre inscription.";

// added v2.7.0-beta - 2011-06-13 by Thorsten
$PMF_LANG["ad_entry_changelog_history"] = "Historique des changements";

// added v2.7.0-beta2 - 2011-06-22 by Thorsten
$LANG_CONF["security.ssoSupport"] = ["checkbox", "Support de l'authentification unique (SSO)"];
$LANG_CONF["security.ssoLogoutRedirect"] = ["input", "URL de retour après déconnexion avec l'authentificatin unique"];
$LANG_CONF["main.dateFormat"] = ["input", "Format de date (défaut: Y-m-d H:i)"];
$LANG_CONF["security.enableLoginOnly"] = ["checkbox", "FAQ complètement sécurisée"];

// added v2.7.0-RC - 2011-08-18 by Thorsten
$PMF_LANG["securityControlCenter"] = "Sécurité";
$PMF_LANG["ad_search_delsuc"] = "Le terme de recherche a été supprimé avec succès.";
$PMF_LANG["ad_search_delfail"] = "Le terme de recherche n'a pas été supprimé.";

// added 2.7.1 - 2011-09-30 by Thorsten
$PMF_LANG["msg_about_faq"] = "À propos de cette FAQ";
$LANG_CONF["security.useSslOnly"] = ["checkbox", "FAQ avec SSL seulement"];
$PMF_LANG["msgTableOfContent"] = "Table des matières";

// added 2.7.5 - 2012-03-02 by Thorsten
$PMF_LANG["msgExportAllFaqs"] = "Tout imprimer en PDF";
$PMF_LANG["ad_online_verification"] = "Vérification en ligne";
$PMF_LANG["ad_verification_button"] = "Cliquez pour vérifier votre installation phpMyFAQ";
$PMF_LANG["ad_verification_notokay"] = "Votre version de phpMyFAQ a été changée localement :";
$PMF_LANG["ad_verification_okay"] = "Votre version phpMyFAQ a été vérifiée avec succés";

// added v2.8.0-alpha - 2011-09-29 by Thorsten
$PMF_LANG["ad_menu_searchfaqs"] = "Chercher dans les FAQs";

// added v2.8.0-alpha - 2012-01-13 by Peter
$LANG_CONF["records.enableCloseQuestion"] = ["checkbox", "Fermer les questions ouvertes après une réponse ?"];
$LANG_CONF["records.enableDeleteQuestion"] = ["checkbox", "Supprimer les questions ouvertes après une réponse ?"];
$PMF_LANG["msg2answerFAQ"] = "Répondu";

// added v2.8.0-alpha - 2012-01-16 by Thorsten
$PMF_LANG["headerUserControlPanel"] = "Paramètres utilisateur";

// added v2.8.0-alpha2 - 2012-03-15 by Thorsten
$PMF_LANG["rememberMe"] = "Se souvenir de moi";
$PMF_LANG["ad_menu_instances"] = "Instances Multisites";


// added v2.8.0-alpha2 - 2012-08-06 by Thorsten
$PMF_LANG["ad_record_inactive"] = "FAQs inactive";
$LANG_CONF["main.maintenanceMode"] = ["checkbox", "Placer la FAQ en mode maintenance"];
$PMF_LANG["msgMode"] = "Modus";
$PMF_LANG["msgMaintenanceMode"] = "La FAQ est en maintenance";
$PMF_LANG["msgOnlineMode"] = "La FAQ est en ligne";

// added v2.8.0-alpha3 - 2012-08-30 by Thorsten
$PMF_LANG["msgShowMore"] = "voir plus";
$PMF_LANG["msgQuestionAnswered"] = "Question répondue";
$PMF_LANG["msgMessageQuestionAnswered"] = "Votre question de la FAQ %s a reçu une réponse. Vous pouvez la consulter ici";
//PMA
// added v2.8.0-alpha3 - 2012-11-03 by Thorsten
$PMF_LANG["rightsLanguage::addattachment"] = "Ajouter des pièces-jointes";
$PMF_LANG["rightsLanguage::editattachment"] = "Editer les pièces-jointes";
$PMF_LANG["rightsLanguage::delattachment"] = "Supprimer les pièces-jointes";
$PMF_LANG["rightsLanguage::dlattachment"] = "Télécharger les pièces-jointes";
$PMF_LANG["rightsLanguage::reports"] = "Générer des rapports";
$PMF_LANG["rightsLanguage::addfaq"] = "Ajouter des FAQs dans le frontend";
$PMF_LANG["rightsLanguage::addquestion"] = "Ajouter des questions dans le frontend";
$PMF_LANG["rightsLanguage::addcomment"] = "Ajouter des commentaires dans le frontend";
$PMF_LANG["rightsLanguage::editinstances"] = "Éditer des instances Multisites";
$PMF_LANG["rightsLanguage::addinstances"] = "Ajouter des instsances Multisites";
$PMF_LANG["rightsLanguage::delinstances"] = "Supprimer les instances Multisites";
$PMF_LANG["rightsLanguage::export"] = "Exporter les FAQs";

// added v2.8.0-beta - 2012-12-24 by Thorsten
$LANG_CONF["records.randomSort"] = ["checkbox", "Trier aléatoirement les FAQs"];
$LANG_CONF["main.enableWysiwygEditorFrontend"] = ["checkbox", "Activer l'éditeur WYSIWYG intégré en frontend"];

// added v2.8.0-beta3 - 2013-01-15 by Thorsten
$LANG_CONF["main.enableGravatarSupport"] = ["checkbox", "Gravatar Support"];

// added v2.8.0-RC - 2013-01-29 by Thorsten
$PMF_LANG["ad_stopwords_desc"] = "Sélectionnez une langue pour ajouter ou modifier des mots vides.";
$PMF_LANG["ad_visits_per_day"] = "Visites par jour";

// added v2.8.0-RC2 - 2013-02-17 by Thorsten
$PMF_LANG["ad_instance_add"] = "Ajouter une nouvelle instance multisite de phpMyFAQ";
$PMF_LANG["ad_instance_error_notwritable"] = "Le répertoire /multisite n'a pas les droits en écriture.";
$PMF_LANG["ad_instance_url"] = "URL de l'instance";
$PMF_LANG["ad_instance_path"] = "Chemin de l'instance";
$PMF_LANG["ad_instance_name"] = "Nom de l'instance";
$PMF_LANG["ad_instance_email"] = "E-mail Admin";
$PMF_LANG["ad_instance_admin"] = "Nom d'utilisateur Admin";
$PMF_LANG["ad_instance_password"] = "Mot de passe Admin";
$PMF_LANG["ad_instance_hint"] = "Attention : La création d'une nouvelle instance phpMyFAQ peut prendre quelques secondes !";
$PMF_LANG["ad_instance_button"] = "Sauvegarder l'instance";
$PMF_LANG["ad_instance_error_cannotdelete"] = "Impossible de supprimer l'instance";
$PMF_LANG["ad_instance_config"] = "Configuration de l'instance";

// added v2.8.0-RC3 - 2013-03-03 by Thorsten
$PMF_LANG["msgAboutThisNews"] = "A propos de cette actualité";

// added v.2.8.1 - 2013-06-23 by Thorsten
$PMF_LANG["msgAccessDenied"] = "Accès refusé.";

// added v.2.8.21 - 2015-02-17 by Thorsten
$PMF_LANG["msgSeeFAQinFrontend"] = "Voir cette FAQ sur le Frontend";

// added v.2.9.0-alpha - 2013-12-26 by Thorsten
$PMF_LANG["msgRelatedTags"] = "Ajouter des tags pour filtrer";
$PMF_LANG["msgPopularTags"] = "Tags les plus populaires";
$LANG_CONF["search.enableHighlighting"] = ["checkbox", "Surligner les termes de recherche"];
$LANG_CONF["records.allowCommentsForGuests"] = ["checkbox", "Autoriser les commentaires pour les invités"];
$LANG_CONF["records.allowQuestionsForGuests"] = ["checkbox", "Autoriser l'ajout de questions pour les invités"];
$LANG_CONF["records.allowNewFaqsForGuests"] = ["checkbox", "Autoriser l'ajout de nouvelles FAQs"];
$PMF_LANG["ad_searchterm_del"] = "Supprimer tous les termes de recherche enregistrés";
$PMF_LANG["ad_searchterm_del_suc"] = "Tous les termes de recherche ont été supprimés avec succès.";
$PMF_LANG["ad_searchterm_del_err"] = "Impossible de supprimer tous les termes de recherche.";
$LANG_CONF["records.hideEmptyCategories"] = ["checkbox", "Cacher les catégories vides"];
$LANG_CONF["search.searchForSolutionId"] = ["checkbox", "Cherche un ID de solution"];
$LANG_CONF["socialnetworks.disableAll"] = ["checkbox", "Désactiver tous les réseaux sociaux"];
$LANG_CONF["main.enableGzipCompression"] = ["checkbox", "Activer la compression GZIP"];

// added v2.9.0-alpha2 - 2014-08-16 by Thorsten
$PMF_LANG["ad_tag_delete_success"] = "Le tag a été supprimé avec succès.";
$PMF_LANG["ad_tag_delete_error"] = "Le tag n'a pas été supprimé à cause d'une erreur.";
$PMF_LANG["seoCenter"] = "SEO";
$LANG_CONF["seo.metaTagsHome"] = ["select", "Metadonnées de la page d'accueil"];
$LANG_CONF["seo.metaTagsFaqs"] = ["select", "Metadonnées des FAQs"];
$LANG_CONF["seo.metaTagsCategories"] = ["select", "Metadonnées des pages de catégorie"];
$LANG_CONF["seo.metaTagsPages"] = ["select", "Metadonnées des pages statiques"];
$LANG_CONF["seo.metaTagsAdmin"] = ["select", "Metadonnées de l'administration"];
$PMF_LANG["msgMatchingQuestions"] = "Les résultats suivants s'approchent de votre question";
$PMF_LANG["msgFinishSubmission"] = "Si aucune des suggestions au-dessus ne correspondent à votre question, alors cliquez sur le bouton ci-dessous pour soumettre votre question.";
$LANG_CONF["spam.manualActivation"] = ["checkbox", "Activation manuelle des nouveaux utilisateurs (défaut: activé)"];

// added v2.9.0-alpha2 - 2014-10-13 by Christopher Andrews ( Chris--A )
$PMF_LANG["mailControlCenter"] = "Paramètre e-mail";
$LANG_CONF["mail.remoteSMTP"] = ["checkbox", "Utiliser un serveur SMTP distant (défaut: déactivé)"];
$LANG_CONF["mail.remoteSMTPServer"] = ["input", "Adresse du serveur"];
$LANG_CONF["mail.remoteSMTPUsername"] = ["input", "Nom d'utilisateur"];
$LANG_CONF["mail.remoteSMTPPassword"] = ["password", "Mot de passe"];
$LANG_CONF["security.enableRegistration"] = ["checkbox", "Activer l'enregistrement pour les visiteurs"];

// added v2.9.0-alpha3 - 2015-02-08 by Thorsten
$LANG_CONF["main.customPdfHeader"] = ["area", "En-tête PDF personnalisée (HTML autorisé)"];
$LANG_CONF["main.customPdfFooter"] = ["area", "Pied-de-page PDF personnalisé (HTML autorisé)"];
$LANG_CONF["records.allowDownloadsForGuests"] = ["checkbox", "Autoriser le téléchargement pour les invités"];
$PMF_LANG["ad_msgNoteAboutPasswords"] = "Attention! Si vous saisissez un mot de passe, vous écraserez le mot de passe de l'utilisateur.";
$PMF_LANG["ad_delete_all_votings"] = "Effacer tous les votes";
$PMF_LANG["ad_categ_moderator"] = "Modérateur";
$PMF_LANG["ad_clear_all_visits"] = "Effacer toutes les visites";
$PMF_LANG["ad_reset_visits_success"] = "Les visites ont été effacées avec succès.";
$LANG_CONF["main.enableMarkdownEditor"] = ["checkbox", "Activer l'éditeur Markdown intégré"];

// added v2.9.0-beta - 2015-09-27 by Thorsten
$PMF_LANG["faqOverview"] = "Aperçu";
$PMF_LANG["ad_dir_missing"] = "Le répertoire %s est manquant.";
$LANG_CONF["main.enableSmartAnswering"] = ["checkbox", "Activer les réponses intelligentes pour les questions des utilisateurs"];

// added v2.9.0-beta2 - 2015-12-23 by Thorsten
$LANG_CONF["search.enableElasticsearch"] = ["checkbox", "Activer le support Elasticsearch"];
$PMF_LANG["ad_menu_elasticsearch"] = "Configuration Elasticsearch";
$PMF_LANG["ad_es_create_index"] = "Créer l'Index";
$PMF_LANG["ad_es_drop_index"] = "Supprimer l'Index";
$PMF_LANG["ad_es_bulk_index"] = "Import complet";
$PMF_LANG["ad_es_create_index_success"] = "Index créé avec succès.";
$PMF_LANG["ad_es_create_import_success"] = "Import réalisé avec succès.";
$PMF_LANG["ad_es_drop_index_success"] = "Index supprimé avec succès.";
$PMF_LANG["ad_export_generate_json"] = "Créer un fichier JSON";
$PMF_LANG["ad_media_name_search"] = "Recherche d'un nom de média";

// added v2.9.0-RC - 2016-02-19 by Thorsten
$PMF_LANG["ad_admin_notes"] = "Notes privées";
$PMF_LANG["ad_admin_notes_hint"] = "%s (visible seulement pour les éditeurs)";

// added v2.9.10 - 2018-02-17 by Thorsten
$PMF_LANG["ad_quick_entry"] = "Ajouter une nouvelle FAQ dans cette catégorie";

// added 2.10.0-alpha - 2016-08-08 by Thorsten
$LANG_CONF["ldap.ldap_mapping.name"] = ["input", "LDAP mapping for name, \"cn\" when using an ADS"];
$LANG_CONF["ldap.ldap_mapping.username"] = ["input", "LDAP mapping for username, \"samAccountName\" when using an ADS"];
$LANG_CONF["ldap.ldap_mapping.mail"] = ["input", "LDAP mapping for email, \"mail\" when using an ADS"];
$LANG_CONF["ldap.ldap_mapping.memberOf"] = ["input", "LDAP mapping for \"member of\" when using LDAP groups"];
$LANG_CONF["ldap.ldap_use_domain_prefix"] = ["checkbox", "LDAP domain prefix, e.g. \"DOMAIN\username\""];
$LANG_CONF["ldap.ldap_options.LDAP_OPT_PROTOCOL_VERSION"] = ["input", "LDAP protocol version (default: 3)"];
$LANG_CONF["ldap.ldap_options.LDAP_OPT_REFERRALS"] = ["input", "LDAP referrals (default: 0)"];
$LANG_CONF["ldap.ldap_use_memberOf"] = ["checkbox", "Enable LDAP group support, e.g. \"DOMAIN\username\""];
$LANG_CONF["ldap.ldap_use_sasl"] = ["checkbox", "Enable LDAP SASL support"];
$LANG_CONF["ldap.ldap_use_multiple_servers"] = ["checkbox", "Enable multiple LDAP servers support"];
$LANG_CONF["ldap.ldap_use_anonymous_login"] = ["checkbox", "Enable anonymous LDAP connections"];
$LANG_CONF["ldap.ldap_use_dynamic_login"] = ["checkbox", "Enable LDAP dynamic user binding"];
$LANG_CONF["ldap.ldap_dynamic_login_attribute"] = ["input", "LDAP attribute for dynamic user binding, \"uid\" when using an ADS"];
$LANG_CONF["seo.enableXMLSitemap"] = ["checkbox", "Activer le sitemap XML"];
$PMF_LANG["ad_category_image"] = "Image de la catégorie";
$PMF_LANG["ad_user_show_home"] = "Afficher sur l'accueil";

// added v.2.10.0-alpha - 2017-11-09 by Brian Potter (BrianPotter)
$PMF_LANG["ad_view_faq"] = "Voir la FAQ";

// added 3.0.0-alpha - 2018-01-04 by Thorsten
$LANG_CONF["main.enableCategoryRestrictions"] = ["checkbox", "Activer les restrictions de catégorie"];
$LANG_CONF["main.enableSendToFriend"] = ["checkbox", "Activer l'envoi à des amis"];
$PMF_LANG["msgUserRemovalText"] = "Vous pouvez demander la suppression de votre compte et de vos données personnelles. Un e-mail sera également envoyé à l'équipe d'administration. L'équipe supprimera votre compte, vos commentaires et vos questions. Etant donné que c'est un processus manuel, cela peut prendre jusqu'à 24h. Suite à cela, vous recevrez une confirmation de suppression par e-mail.";
$PMF_LANG["msgUserRemoval"] = "Demander la suppression de l'utilisateur";
$PMF_LANG["ad_menu_RequestRemove"] = "Demander la suppression du compte";
$PMF_LANG["msgContactRemove"] = "Demande de suppression à l'équipe d'administration";
$PMF_LANG["msgContactPrivacyNote"] = "Veuillez prendre connaissance de notre";
$PMF_LANG["msgPrivacyNote"] = "Déclaration de confidentialité";

// added 3.0.0-alpha2 - 2018-03-27 by Thorsten
$LANG_CONF["main.enableAutoUpdateHint"] = ["checkbox", "Vérifier automatiquement les nouvelles versions"];
$PMF_LANG["ad_user_is_superadmin"] = "Super-Admin";
$PMF_LANG["ad_user_overwrite_passwd"] = "Écraser le mot de passe";
$LANG_CONF["records.enableAutoRevisions"] = ["checkbox", "Créer une nouvelle révision pour tous les modifications de FAQ"];
$PMF_LANG["rightsLanguage::view_faqs"] = "Voir les FAQs";
$PMF_LANG["rightsLanguage::view_categories"] = "Voir les catégories";
$PMF_LANG["rightsLanguage::view_sections"] = "Voir les sections";
$PMF_LANG["rightsLanguage::view_news"] = "Voir les actualités";
$PMF_LANG["rightsLanguage::add_section"] = "Ajouter des sections";
$PMF_LANG["rightsLanguage::edit_section"] = "Editer les sections";
$PMF_LANG["rightsLanguage::delete_section"] = "Supprimer les sections";
$PMF_LANG["rightsLanguage::administrate_sections"] = "Administrer les sections";
$PMF_LANG["rightsLanguage::administrate_groups"] = "Administrer les groupes";
$PMF_LANG["ad_group_rights"] = "Permissions de groupe";
$PMF_LANG["ad_menu_meta"] = "Modèles de métadonnée";
$PMF_LANG["ad_meta_add"] = "Ajouter un modèle de métadonnée";
$PMF_LANG["ad_meta_page_id"] = "Page type";
$PMF_LANG["ad_meta_type"] = "Contenu type";
$PMF_LANG["ad_meta_content"] = "Contenu";
$PMF_LANG["ad_meta_copy_snippet"] = "Copier l'extrait de code pour les modèles";

// added v3.0.0-alpha.3 - 2018-09-20 by Timo
$PMF_LANG["ad_menu_section_administration"] = "Sections";
$PMF_LANG["ad_section_add"] = "Ajouter une section";
$PMF_LANG["ad_section_add_link"] = "Ajouter une section";
$PMF_LANG["ad_sections"] = "Sections";
$PMF_LANG["ad_section_details"] = "Détails de la section";
$PMF_LANG["ad_section_name"] = "Nom";
$PMF_LANG["ad_section_description"] = "Description";
$PMF_LANG["ad_section_membership"] = "Membres de la section";
$PMF_LANG["ad_section_members"] = "Membres";
$PMF_LANG["ad_section_addMember"] = "+";
$PMF_LANG["ad_section_removeMember"] = "-";
$PMF_LANG["ad_section_deleteSection"] = "Supprimer la section";
$PMF_LANG["ad_section_deleteQuestion"] = "Êtes-vous sûr de vouloir supprimer cette section ?";
$PMF_LANG["ad_section_error_delete"] = "Cette section n'a pas pu être supprimée. ";
$PMF_LANG["ad_section_error_noName"] = "Merci de saisir un nom de section. ";
$PMF_LANG["ad_section_suc"] = "Cette section a été ajoutée avec <strong>succès</strong>.";
$PMF_LANG["ad_section_deleted"] = "Cette section a été ajoutée avec succès.";
$PMF_LANG["rightsLanguage::viewadminlink"] = "Voir le lien vers l'administration";

// added v3.0.0-beta.3 - 2019-09-22 by Thorsten
$LANG_CONF["mail.remoteSMTPPort"] = ["input", "Port du serveur SMTP"];
$LANG_CONF["mail.remoteSMTPEncryption"] = ["input", "Chiffrement du serveur SMTP"];
$PMF_LANG["ad_record_faq"] = "Question et réponse";
$PMF_LANG["ad_record_permissions"] = "Permissions";
$PMF_LANG["loginPageMessage"] = "Connexion à ";

// added v3.0.5 - 2020-10-03 by Thorsten
$PMF_LANG["ad_menu_faq_meta"] = "Métadonnées";

// added v3.0.8 - 2021-01-22
$LANG_CONF["main.privacyURL"] = ["input", "URL pour la note de confidentialité"];

// added v3.1.0-alpha - 2020-03-27 by Thorsten
$PMF_LANG["ad_user_data_is_visible"] = "Le nom d'utilisateur est visible";
$PMF_LANG["ad_user_is_visible"] = "Visible";
$PMF_LANG["ad_categ_save_order"] = "Enregistrer l'ordre";
$PMF_LANG["ad_add_user_change_password"] = "L'utilisateur doit changer son mot de passe à la première connexion";
$LANG_CONF["api.enableAccess"] = ["checkbox", "API REST"];
$LANG_CONF["api.apiClientToken"] = ["input", "API Client Token"];
$LANG_CONF["security.domainWhiteListForRegistrations"] = ["area", "Hôtes autorisées pour l'inscription"];
$LANG_CONF["security.loginWithEmailAddress"] = ["checkbox", "Se logger uniquement avec l'adresse email"];

return $PMF_LANG;
