<?php

/**
 * Spanish language file
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Eduardo Polidor
 * @author Ivan Gil
 * @author Lisandro López Villatoro
 * @author Luis Carvalho <luis.carvalho@iberweb.pt>
 * @copyright 2004-2020 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2004-06-24
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
 *  Please be consistent with this format as we need it for
 *  the translation tool to work properly
 */

$PMF_LANG['metaCharset'] = 'UTF-8';
$PMF_LANG['metaLanguage'] = 'es';
// ltr: left to right (e.g. English language); rtl: right to left (e.g. Arabic language)
$PMF_LANG['language'] = 'Spanish';
$PMF_LANG['dir'] = 'ltr';
$PMF_LANG['nplurals'] = '2';

// Navegación
$PMF_LANG['msgCategory'] = 'Categorías';
$PMF_LANG['msgShowAllCategories'] = 'Todas las categorías';
$PMF_LANG['msgSearch'] = 'Buscar';
$PMF_LANG['msgAddContent'] = 'Añadir FAQ';
$PMF_LANG['msgQuestion'] = 'Añadir pregunta';
$PMF_LANG['msgOpenQuestions'] = 'Preguntas abiertas';
$PMF_LANG['msgHelp'] = 'Ayuda';
$PMF_LANG['msgContact'] = 'Contacto';
$PMF_LANG['msgHome'] = 'Inicio';
$PMF_LANG['msgNews'] = 'Noticias';
$PMF_LANG['msgUserOnline'] = ' Usuarios en línea';
$PMF_LANG['msgXMLExport'] = "Exportación XML";
$PMF_LANG['msgBack2Home'] = 'Volver a la página principal';

// Páginas de contenido
$PMF_LANG['msgFullCategories'] = 'Categorías';
$PMF_LANG['msgFullCategoriesIn'] = 'Otras categorías de ';
$PMF_LANG['msgSubCategories'] = 'Subcategorías';
$PMF_LANG['msgEntries'] = 'FAQs';
$PMF_LANG['msgEntriesIn'] = 'Preguntas en ';
$PMF_LANG['msgViews'] = 'Vistas';
$PMF_LANG['msgPage'] = 'Página ';
$PMF_LANG['msgPages'] = ' Páginas';
$PMF_LANG['msgPrevious'] = 'Anterior';
$PMF_LANG['msgNext'] = 'Siguiente';
$PMF_LANG['msgCategoryUp'] = 'subir una categoría';
$PMF_LANG['msgLastUpdateArticle'] = 'Última modificación: ';
$PMF_LANG['msgAuthor'] = 'Autor: ';
$PMF_LANG['msgPrinterFriendly'] = 'Versión para imprimir';
$PMF_LANG['msgPrintArticle'] = 'Imprimir este registro';
$PMF_LANG['msgMakeXMLExport'] = 'Exportar como Archivo-XML';
$PMF_LANG['msgAverageVote'] = 'Valoración media:';
$PMF_LANG['msgVoteUsability'] = 'Valora esta FAQ';
$PMF_LANG['msgVoteFrom'] = 'de';
$PMF_LANG['msgVoteBad'] = 'totalmente inútil';
$PMF_LANG['msgVoteGood'] = 'más útil';
$PMF_LANG['msgVotings'] = 'Votos ';
$PMF_LANG['msgVoteSubmit'] = 'Votar';
$PMF_LANG['msgVoteThanks'] = '¡Muchas gracias por tu voto!';
$PMF_LANG['msgYouCan'] = 'Puedes ';
$PMF_LANG['msgWriteComment'] = 'añadir un comentario';
$PMF_LANG['msgShowCategory'] = 'Tabla de contenido: ';
$PMF_LANG['msgCommentBy'] = 'Comentario de ';
$PMF_LANG['msgCommentHeader'] = 'Comenta esta FAQ';
$PMF_LANG['msgYourComment'] = 'Comentario';
$PMF_LANG['msgCommentThanks'] = '¡Muchas gracias por tu comentario!';
$PMF_LANG['msgSeeXMLFile'] = 'Abrir Archivo XML';
$PMF_LANG['msgSend2Friend'] = 'Enviar FAQ a un amigo';
$PMF_LANG['msgS2FName'] = 'Tu nombre:';
$PMF_LANG['msgS2FEMail'] = 'Tu e-mail';
$PMF_LANG['msgS2FFriends'] = 'Tus amigos';
$PMF_LANG['msgS2FEMails'] = '. e-mail';
$PMF_LANG['msgS2FText'] = 'Se enviará el siguiente texto';
$PMF_LANG['msgS2FText2'] = 'Encontrarás la FAQ en la siguiente dirección';
$PMF_LANG['msgS2FMessage'] = 'Mensaje adicional para tus amigos';
$PMF_LANG['msgS2FButton'] = 'enviar correo';
$PMF_LANG['msgS2FThx'] = '¡Gracias por tu recomendación!';
$PMF_LANG['msgS2FMailSubject'] = 'Recomendación de ';

// Buscar
$PMF_LANG['msgSearchWord'] = 'Palabra clave';
$PMF_LANG['msgSearchFind'] = 'Resultados de la búsqueda para ';
$PMF_LANG['msgSearchAmount'] = ' Resultado de la búsqueda';
$PMF_LANG['msgSearchAmounts'] = ' Resultados de búsqueda';
$PMF_LANG['msgSearchCategory'] = 'Categoría: ';
$PMF_LANG['msgSearchContent'] = 'Respuesta: ';

// nuevo Contenido
$PMF_LANG['msgNewContentHeader'] = 'Propuesta para una nueva entrada en la FAQ';
$PMF_LANG['msgNewContentAddon'] = 'Tu propuesta de FAQ no se publica inmediatamente, debe ser revisada por un moderador. Son campos obligatorios <strong>Nombre</strong>, <strong>E-mail</strong>, <strong>Categoría</strong>, <strong>Pregunta</strong> y <strong>Respuesta</strong>. Por favor, separa las palabras clave sólo con comas.';
$PMF_LANG['msgNewContentName'] = 'Nombre:';
$PMF_LANG['msgNewContentMail'] = 'E-mail:';
$PMF_LANG['msgNewContentCategory'] = 'Categoría:';
$PMF_LANG['msgNewContentTheme'] = 'Pregunta:';
$PMF_LANG['msgNewContentArticle'] = 'Respuesta';
$PMF_LANG['msgNewContentKeywords'] = 'Palabras clave:';
$PMF_LANG['msgNewContentLink'] = 'Enlace para esta FAQ';
$PMF_LANG['msgNewContentSubmit'] = 'Enviar';
$PMF_LANG['msgInfo'] = 'Más información en: ';
$PMF_LANG['msgNewContentThanks'] = '¡Muchas gracias por esta sugerencia!';

// hacer pregunta
$PMF_LANG['msgNewQuestion'] = "Esta página puede utilizarse para hacer preguntas a los lectores de las FAQ y así promover nuevas entradas de FAQ. ¡Sólo haciendo preguntas podemos averiguar los temas para los que se desean respuestas! Las preguntas formuladas aparecen en la Categoría de Preguntas abiertas.";
$PMF_LANG['msgAskCategory'] = 'Categoría:';
$PMF_LANG['msgAskYourQuestion'] = 'Pregunta:';
$PMF_LANG['msgAskThx4Mail'] = 'Muchas gracias por tu pregunta';
$PMF_LANG['msgDate_User'] = 'Fecha / Autor';
$PMF_LANG['msgQuestion2'] = 'Pregunta';
$PMF_LANG['msg2answer'] = 'responder';
$PMF_LANG['msgQuestionText'] = 'Aquí puedes ver las preguntas de otros usuarios. Puedes contestar aquí. La entrada también se añadirá a las entradas de las FAQ.';
$PMF_LANG['msgNoQuestionsAvailable'] = 'Actualmente no hay preguntas pendientes.';

// Contacto
$PMF_LANG['msgContactEMail'] = 'e-mail al admin de la FAQ';
$PMF_LANG['msgMessage'] = 'Mensaje';

// Página de inicio
$PMF_LANG['msgTopTen'] = "FAQs populares";
$PMF_LANG['msgHomeThereAre'] = "Hay ";
$PMF_LANG['msgHomeArticlesOnline'] = " FAQs disponibles.";
$PMF_LANG['msgNoNews'] = "No tener noticias es una buena noticia.";
$PMF_LANG['msgLatestArticles'] = "Últimas FAQs";

// Notificación por correo electrónico
$PMF_LANG['msgMailThanks'] = "Muchas gracias por tu propuesta";
$PMF_LANG['msgMailCheck'] = "Hay una nueva entrada en la FAQ. ¡Por favor, compruebe la sección de administración!";
$PMF_LANG['msgMailContact'] = "Tu mensaje ha sido enviado al administrador";

// Mensajes de error
$PMF_LANG['err_noDatabase'] = "!No hay conexión con la base de datos!";
$PMF_LANG['err_noHeaders'] = "¡No se encontró ninguna categoría!";
$PMF_LANG['err_noArticles'] = "Aún no hay FAQs.";
$PMF_LANG['err_badID'] = "¡ID incorrecto!";
$PMF_LANG['err_noTopTen'] = "Aún no hay FAQs populares disponibles.";
$PMF_LANG['err_nothingFound'] = "No se encontró ninguna entrada.";
$PMF_LANG['err_SaveEntries'] = "¡Los campos obligatorios son <strong>Nombre</strong>, <strong>e-mail</strong>, <strong>Categoría</strong>, <strong>Pregunta</strong> y <strong>Respuesta</strong>!";
$PMF_LANG['err_SaveComment'] = "¡Los campos obligatorios son <strong>Nombre</strong>, <strong>e-mail</strong> y <strong>Comentario</strong>!";
$PMF_LANG['err_VoteTooMuch'] = = "Desafortunadamente, la calificación no pudo ser salvada, porque la IP ya fue utilizada para la calificación.";
$PMF_LANG['err_noVote'] = "¡No se dio ninguna calificación!";
$PMF_LANG['err_noMailAdress'] = "La dirección de e-mail proporcionada es incorrecta.";
$PMF_LANG['err_sendMail'] = "¡Los campos obligatorios incluyen <strong>Nombre</strong> y <strong>e-mail</strong>!";

// Ayuda para la búsqueda
$PMF_LANG['help_search'] = "<strong>Encuentra la respuesta:</strong><br>Buscando <strong style=\"color: Red;\">Term1 Term2 </strong>permite buscar dos o más términos, en orden descendente de relevancia. <strong>Nota: </strong>el término de búsqueda debe tener al menos 4 caracteres, las consultas más cortas se rechazan automáticamente.";

// Menú
$PMF_LANG['ad'] = 'SECCIÓN ADMIN';
$PMF_LANG['ad_menu_user_administration'] = 'Usuarios';
$PMF_LANG['ad_menu_entry_aprove'] = 'Aprobar FAQs';
$PMF_LANG['ad_menu_entry_edit'] = 'Editar FAQs';
$PMF_LANG['ad_menu_categ_add'] = 'Añadir categoría';
$PMF_LANG['ad_menu_categ_edit'] = 'Editar categoría';
$PMF_LANG['ad_menu_news_add'] = 'Añadir Noticias';
$PMF_LANG['ad_menu_news_edit'] = 'Editar Noticias';
$PMF_LANG['ad_menu_open'] = 'Preguntas abiertas';
$PMF_LANG['ad_menu_stat'] = 'Estadísticas';
$PMF_LANG['ad_menu_cookie'] = 'Cookies';
$PMF_LANG['ad_menu_session'] = 'Ver Sesiones';
$PMF_LANG['ad_menu_adminlog'] = 'Ver Adminlog';
$PMF_LANG['ad_menu_passwd'] = 'Cambiar Contraseña';
$PMF_LANG['ad_menu_logout'] = 'Salir';
$PMF_LANG['ad_menu_startpage'] = 'Inicio';

// Mensajes
$PMF_LANG['ad_msg_identify'] = 'Por favor identifícate.';
$PMF_LANG['ad_msg_passmatch'] = '¡Ambas contraseñas deben <strong>coincidir</strong>!';
$PMF_LANG['ad_msg_savedsuc_1'] = 'Perfil de';
$PMF_LANG['ad_msg_savedsuc_2'] = 'se guardó correctamente.';
$PMF_LANG['ad_msg_mysqlerr'] = 'Debido a un error de <strong>base de datos</strong> el perfil no pudo ser guardado.';
$PMF_LANG['ad_msg_noauth'] = 'No estás autorizado.';

// General
$PMF_LANG['ad_gen_page'] = 'Página';
$PMF_LANG['ad_gen_of'] = 'de';
$PMF_LANG['ad_gen_lastpage'] = 'Página anterior';
$PMF_LANG['ad_gen_nextpage'] = 'Página siguiente';
$PMF_LANG['ad_gen_save'] = 'Guardar';
$PMF_LANG['ad_gen_reset'] = 'Resetear';
$PMF_LANG['ad_gen_yes'] = 'Sí';
$PMF_LANG['ad_gen_no'] = 'No';
$PMF_LANG['ad_gen_top'] = 'Inicio de página';
$PMF_LANG['ad_gen_ncf'] = 'No se encontró ninguna categoría';
$PMF_LANG['ad_gen_delete'] = 'Eliminar';
$PMF_LANG['ad_gen_or'] = "o";

// Administración de usuarios
$PMF_LANG['ad_user'] = 'Administración de Usuarios';
$PMF_LANG['ad_user_username'] = 'Usuarios registrados';
$PMF_LANG['ad_user_rights'] = 'Permisos';
$PMF_LANG['ad_user_edit'] = 'Editar';
$PMF_LANG['ad_user_delete'] = 'Eliminar';
$PMF_LANG['ad_user_add'] = 'Añadir usuario';
$PMF_LANG['ad_user_profou'] = 'Perfil del usuario';
$PMF_LANG['ad_user_name'] = 'Nombre';
$PMF_LANG['ad_user_contraseña'] = 'Contraseña';
$PMF_LANG['ad_user_confirm'] = 'Confirmar';
$PMF_LANG['ad_user_del_1'] = '¿El usuario ';
$PMF_LANG['ad_user_del_2'] = 'debería eliminarse?';
$PMF_LANG['ad_user_del_3'] = '¿Estás seguro?';
$PMF_LANG['ad_user_deleted'] = 'El usuario ha sido eliminado.';
$PMF_LANG['ad_user_checkall'] = 'Seleccionar todos';

// Gestión de las contribuciones
$PMF_LANG['ad_entry_aor'] = 'Resumen de FAQs ';
$PMF_LANG['ad_entry_id'] = 'ID';
$PMF_LANG['ad_entry_topic'] = 'Pregunta';
$PMF_LANG['ad_entry_action'] = 'Acción';
$PMF_LANG['ad_entry_edit_1'] = 'FAQ';
$PMF_LANG['ad_entry_edit_2'] = 'editar';
$PMF_LANG['ad_entry_theme'] = 'Pregunta';
$PMF_LANG['ad_entry_content'] = 'Respuesta';
$PMF_LANG['ad_entry_keywords'] = 'Buscar palabras claves:';
$PMF_LANG['ad_entry_author'] = 'Autor';
$PMF_LANG['ad_entry_category'] = 'Categoría';
$PMF_LANG['ad_entry_active'] = 'Activado';
$PMF_LANG['ad_entry_date'] = 'Fecha';
$PMF_LANG['ad_entry_status'] = 'Estado de la FAQ';
$PMF_LANG['ad_entry_changed'] = '¿Qué se cambió?';
$PMF_LANG['ad_entry_changelog'] = 'Cambios';
$PMF_LANG['ad_entry_commentby'] = 'Comentario de';
$PMF_LANG['ad_entry_comment'] = 'Comentarios';
$PMF_LANG['ad_entry_save'] = 'Guardar';
$PMF_LANG['ad_entry_delete'] = 'Eliminar';
$PMF_LANG['ad_entry_delcom_1'] = '¿Estás seguro que el comentario del usuario ';
$PMF_LANG['ad_entry_delcom_2'] = 'debería ser eliminado?';
$PMF_LANG['ad_entry_commentdelsuc'] = 'El comentario fue <strong>correctamente</strong> eliminado.';
$PMF_LANG['ad_entry_back'] = 'Volver a la FAQ';
$PMF_LANG['ad_entry_commentdelfail'] = 'El comentario <strong>no</strong> se ha eliminado.';
$PMF_LANG['ad_entry_savedsuc'] = 'Los cambios se guardaron <strong>con éxito</strong>.';
$PMF_LANG['ad_entry_savedfail'] = 'Ha ocurrido un error en la <strong>base de datos</strong>.';
$PMF_LANG['ad_entry_del_1'] = '¿Seguro que la FAQ';
$PMF_LANG['ad_entry_del_2'] = 'de';
$PMF_LANG['ad_entry_del_3'] = 'debería ser eliminado?';
$PMF_LANG['ad_entry_delsuc'] = 'Entrada de la FAQ eliminado <strong>correctamente</strong>.';
$PMF_LANG['ad_entry_delfail'] = 'Entrada de la FAQ no ha sido <strong>eliminada</strong>!';
$PMF_LANG['ad_entry_back'] = "Volver";

// Gestión de noticias
$PMF_LANG['ad_news_header'] = 'Cabecera';
$PMF_LANG['ad_news_text'] = 'Texto';
$PMF_LANG['ad_news_link_url'] = 'link';
$PMF_LANG['ad_news_link_title'] = 'Título del link:';
$PMF_LANG['ad_news_link_target'] = 'Destino del link';
$PMF_LANG['ad_news_link_window'] = 'Nueva ventana';
$PMF_LANG['ad_news_link_faq'] = 'Dentro del FAQ';
$PMF_LANG['ad_news_add'] = 'Añadir noticias';
$PMF_LANG['ad_news_id'] = '#';
$PMF_LANG['ad_news_headline'] = 'Titular';
$PMF_LANG['ad_news_date'] = 'Fecha';
$PMF_LANG['ad_news_action'] = 'Acción';
$PMF_LANG['ad_news_update'] = 'actualizar';
$PMF_LANG['ad_news_delete'] = 'Eliminar';
$PMF_LANG['ad_news_nodata'] = 'No hay datos en la base de datos';
$PMF_LANG['ad_news_updatesuc'] = 'La noticia ha sido guardada <strong>correctamente</strong>.';
$PMF_LANG['ad_news_del'] = '¿Seguro que deseas <strong>eliminar</strong> esta noticia?';
$PMF_LANG['ad_news_yesdelete'] = '¡Sí, eliminar!';
$PMF_LANG['ad_news_nodelete'] = '¡No!';
$PMF_LANG['ad_news_delsuc'] = 'La noticia ha sido <strong>eliminada con éxito</strong>.';
$PMF_LANG['ad_news_updatenews'] = 'Editar noticia';

// Gestión de la categoría
$PMF_LANG['ad_categ_new'] = 'Añadir nueva categoría';
$PMF_LANG['ad_categ_catnum'] = 'Número de categoría:';
$PMF_LANG['ad_categ_subcatnum'] = 'Número de subcategoría:';
$PMF_LANG['ad_categ_nya'] = '<em>¡Aún no disponible!</em>';
$PMF_LANG['ad_categ_titel'] = 'Nombre de la categoría';
$PMF_LANG['ad_categ_add'] = 'Añadir categoría';
$PMF_LANG['ad_categ_existing'] = 'Categorías existentes';
$PMF_LANG['ad_categ_id'] = '#';
$PMF_LANG['ad_categ_categ'] = 'ID de la categoría';
$PMF_LANG['ad_categ_subcateg'] = 'ID de la subcategoría';
$PMF_LANG['ad_categ_titel'] = "Nombre de la categoría";
$PMF_LANG['ad_categ_action'] = 'Acción';
$PMF_LANG['ad_categ_update'] = 'Editar';
$PMF_LANG['ad_categ_delete'] = 'Eliminar';
$PMF_LANG['ad_categ_updatecateg'] = 'Actualizar categoría';
$PMF_LANG['ad_categ_nodata'] = 'No hay datos en la base de datos';
$PMF_LANG['ad_categ_remark'] = 'Tenga en cuenta que si se elimina una categoría, las FAQs de la categoría eliminada ya no se mostrarán. A continuación, se debe asignar una nueva categoría a la FAQ o eliminarla.';
$PMF_LANG['ad_categ_edit_1'] = 'Editar categoría';
$PMF_LANG['ad_categ_edit_2'] = '';
$PMF_LANG['ad_categ_added'] = 'La categoría fue añadida.';
$PMF_LANG['ad_categ_updated'] = 'La categoría fue actualizada.';
$PMF_LANG['ad_categ_del_yes'] = '¡Sí, eliminar!';
$PMF_LANG['ad_categ_del_no'] = '¡No!';
$PMF_LANG['ad_categ_deletesure'] = '¿Seguro que quieres eliminar esta categoría?';
$PMF_LANG['ad_categ_deleted'] = 'Categoría eliminada.';

// Galletas
$PMF_LANG['ad_cookie_cookiesuc'] = 'La Cookie fue <strong>correctamente</strong> guardada.';
$PMF_LANG['ad_cookie_already'] = 'Ya estaba guardada una Cookie. Dispones de las siguientes opciones';
$PMF_LANG['ad_cookie_again'] = 'Volver a guardar la Cookie';
$PMF_LANG['ad_cookie_delete'] = 'Eliminar la cookie';
$PMF_LANG['ad_cookie_no'] = 'Todavía no se ha guardado ninguna Cookie. Una cookie almacena la información de acceso para que no tenga que ser introducida una y otra vez. Tienes las siguientes opciones:';
$PMF_LANG['ad_cookie_set'] = 'Guardar la Cookie';
$PMF_LANG['ad_cookie_deleted'] = 'Cookie eliminada correctamente.';

// Adminlog
$PMF_LANG['ad_adminlog'] = 'Adminlog';

// Contraseña
$PMF_LANG['ad_passwd_cop'] = 'Cambiar contraseña';
$PMF_LANG['ad_passwd_old'] = 'Contraseña anterior';
$PMF_LANG['ad_passwd_new'] = 'Nueva contraseña';
$PMF_LANG['ad_passwd_con'] = 'Confirmar contraseña';
$PMF_LANG['ad_passwd_change'] = 'Guardar cambio';
$PMF_LANG['ad_passwd_suc'] = 'Contraseña cambiada con éxito.';
$PMF_LANG['ad_passwd_remark'] = '<strong>ATENCIÓN:</strong><br>¡La Cookie debe ser reseteada!';
$PMF_LANG['ad_passwd_fail'] = 'La contraseña anterior debe ser introducida <strong>correctamente</strong> y las dos nuevas tienen que <strong>coincidir</strong>.';

// Adduser
$PMF_LANG['ad_adus_adduser'] = 'Añadir usuario';
$PMF_LANG['ad_adus_name'] = 'Nombre';
$PMF_LANG['ad_adus_contraseña'] = 'Contraseña';
$PMF_LANG['ad_adus_add'] = 'Añadir';
$PMF_LANG['ad_adus_suc'] = 'Usuario añadido <strong>correctamente</strong>.';
$PMF_LANG['ad_adus_edit'] = 'Editar perfil';
$PMF_LANG['ad_adus_dberr'] = '<strong>¡Error de la base de datos!</strong>';
$PMF_LANG['ad_adus_exerr'] = 'El nombre de usuario <strong>ya existe</strong>.';

// Sesiones
$PMF_LANG['ad_sess_id'] = 'ID';
$PMF_LANG['ad_sess_sid'] = 'ID de sesión';
$PMF_LANG['ad_sess_ip'] = 'Dirección IP';
$PMF_LANG['ad_sess_time'] = 'tiempo';
$PMF_LANG['ad_sess_pageviews'] = 'Acciones';
$PMF_LANG['ad_sess_search'] = 'Búsqueda';
$PMF_LANG['ad_sess_sfs'] = 'Búsqueda de sesión';
$PMF_LANG['ad_sess_s_ip'] = 'IP:';
$PMF_LANG['ad_sess_s_minct'] = 'Acciones min.';
$PMF_LANG['ad_sess_s_date'] = 'Fecha';
$PMF_LANG['ad_sess_s_after'] = 'después';
$PMF_LANG['ad_sess_s_before'] = 'antes';
$PMF_LANG['ad_sess_s_search'] = 'Buscar';
$PMF_LANG['ad_sess_session'] = 'Sesión';
$PMF_LANG['ad_sess_r'] = 'Resultados de búsqueda de';
$PMF_LANG['ad_sess_referer'] = 'Referer';
$PMF_LANG['ad_sess_browser'] = 'Navegador';
$PMF_LANG['ad_sess_ai_rubrik'] = 'Categoría';
$PMF_LANG['ad_sess_ai_artikel'] = 'Artículo';
$PMF_LANG['ad_sess_ai_sb'] = 'Términos de búsqueda';
$PMF_LANG['ad_sess_ai_sid'] = 'ID de sesión';
$PMF_LANG['ad_sess_back'] = 'Volver';
$PMF_LANG['ad_sess_noentry'] = 'Sin entradas';

// Estadísticas
$PMF_LANG['ad_rs'] = 'Estadísticas de evaluación';
$PMF_LANG['ad_rs_rating_1'] = 'La calificación de';
$PMF_LANG['ad_rs_rating_2'] = 'vistas:';
$PMF_LANG['ad_rs_red'] = 'Rojo';
$PMF_LANG['ad_rs_green'] = 'Verde';
$PMF_LANG['ad_rs_altt'] = 'con una media inferior al 20%';
$PMF_LANG['ad_rs_ahtf'] = 'con una media superior al 80%';
$PMF_LANG['ad_rs_no'] = 'No hay clasificaciones disponible.';

// Auth
$PMF_LANG['ad_auth_insert'] = 'Por favor, introduce tu nombre de usuario y contraseña.';
$PMF_LANG['ad_auth_user'] = 'Nombre';
$PMF_LANG['ad_auth_passwd'] = 'Contraseña';
$PMF_LANG['ad_auth_ok'] = 'OK';
$PMF_LANG['ad_auth_reset'] = 'Limpiar';
$PMF_LANG['ad_auth_fail'] = 'Usuario o contraseña incorrectos.';
$PMF_LANG['ad_auth_sess'] = 'ID de sesión inválido/caducado.';

// Añadido v0.8 - 24.05.2001 - Bastian - Admin
$PMF_LANG['ad_config_edit'] = 'Editar configuración';
$PMF_LANG['ad_config_save'] = 'Guardar configuración';
$PMF_LANG['ad_config_reset'] = 'Resetear';
$PMF_LANG['ad_config_saved'] = 'La configuración se ha guardado correctamente.';
$PMF_LANG['ad_menu_editconfig'] = 'Configuración de la FAQ';
$PMF_LANG['ad_att_none'] = 'No hay archivos adjuntos disponibles';
$PMF_LANG['ad_att_add'] = 'Adjuntar archivo';
$PMF_LANG['ad_entryins_suc'] = 'Registro guardado con éxito.';
$PMF_LANG['ad_entryins_fail'] = 'Ha ocurrido un error.';
$PMF_LANG['ad_att_del'] = 'Eliminar';
$PMF_LANG['ad_att_nope'] = 'Los archivos adjuntos sólo puden añadirse durante la edición.';
$PMF_LANG['ad_att_delsuc'] = 'Adjunto eliminado correctamente.';
$PMF_LANG['ad_att_delfail'] = 'Ha ocurrido un error al eliminar el archivo adjunto.';
$PMF_LANG['ad_entry_add'] = 'Crear FAQ';

// Añadido v0.85 - 08.06.2001 - Bastian - Admin
$PMF_LANG['ad_csv_make'] = 'Un backup es una imagen completa de las tablas SQL de la FAQ. El formato de la copia es un archivo SQL normal, que puede ser restaurado usando herramientas como phpMyAdmin o similares.';
$PMF_LANG['ad_csv_link'] = 'Descargar backup';
$PMF_LANG['ad_csv_head'] = 'Hacer backup';
$PMF_LANG['ad_att_addto'] = 'Añadir adjunto';
$PMF_LANG['ad_att_addto_2'] = '';
$PMF_LANG['ad_att_att'] = 'Seleccionar archivo';
$PMF_LANG['ad_att_butt'] = 'Subir';
$PMF_LANG['ad_att_suc'] = 'El archivo se adjuntó correctamente.';
$PMF_LANG['ad_att_fail'] = 'Ha habido un error al adjuntar el archivo.';
$PMF_LANG['ad_att_close'] = 'Cerrar esta ventana';

// Añadido v0.85 - 08.07.2001 - Bastian - Admin
$PMF_LANG['ad_csv_restore'] = 'Aquí puedes subir un archivo de backup de phpMyFAQ previamente creado. Ten en cuenta que la recarga de un backup restablecerá el estado de la FAQ al que tenía cuando se creó el backup, es decir, los datos actuales serán reemplazados.';
$PMF_LANG['ad_csv_file'] = 'Archivo';
$PMF_LANG['ad_csv_ok'] = 'Subir e importar archivo';
$PMF_LANG['ad_csv_linklog'] = 'Backup de LOGs';
$PMF_LANG['ad_csv_linkdat'] = 'Backup de datos';
$PMF_LANG['ad_csv_head2'] = 'Importar backup';
$PMF_LANG['ad_csv_no'] = 'Esto <strong>no parece</strong> un backup de phpmyfaq.';
$PMF_LANG['ad_csv_prepare'] = 'Preparando consultas de la base de datos...';
$PMF_LANG['ad_csv_process'] = 'Ejecutando consultas...';
$PMF_LANG['ad_csv_of'] = 'de';
$PMF_LANG['ad_csv_suc'] = 'fueron correctas.';
$PMF_LANG['ad_csv_respaldo'] = 'Backup';
$PMF_LANG['ad_csv_rest'] = 'Restaurar un backup';

// Añadido v0.8 - 25.05.2001 - Bastian - Admin
$PMF_LANG['ad_menu_respaldo'] = 'Backup';
$PMF_LANG['ad_logout'] = 'Sesión terminada correctamente.';
$PMF_LANG["ad_news_add"] = "Añadir noticias";
$PMF_LANG['ad_news_edit'] = 'Editar noticias';
$PMF_LANG['ad_cookie'] = 'Cookies';
$PMF_LANG['ad_sess_head'] = 'Ver sesiones';

// Añadido v1.1 - 06.01.2002 - Bastian
$PMF_LANG['ad_menu_stat'] = "Estadísticas";
$PMF_LANG['ad_kateg_add'] = 'Añadir categoría principal';
$PMF_LANG['ad_kateg_rename'] = 'Editar';
$PMF_LANG['ad_adminlog_date'] = 'Fecha';
$PMF_LANG['ad_adminlog_user'] = 'Usuario';
$PMF_LANG['ad_adminlog_ip'] = 'Dirección IP';

$PMF_LANG['ad_stat_sess'] = 'Sesiones';
$PMF_LANG['ad_stat_days'] = 'Días';
$PMF_LANG['ad_stat_vis'] = 'Sesiones (visitas)';
$PMF_LANG['ad_stat_vpd'] = 'Visitas por día';
$PMF_LANG['ad_stat_fien'] = 'Primera entrada';
$PMF_LANG['ad_stat_laen'] = 'Última entrada';
$PMF_LANG['ad_stat_browse'] = 'Estadísticas de visitas';
$PMF_LANG['ad_stat_ok'] = 'OK';

$PMF_LANG["ad_sess_time"] = "Tiempo";
$PMF_LANG["ad_sess_sid"] = "ID session";
$PMF_LANG["ad_sess_ip"] = "Dirección IP";

$PMF_LANG['ad_ques_take'] = 'Responder la pregunta';
$PMF_LANG['no_cats'] = '¡No se encontraron categorías!';

// Añadido v1.1 - 17.01.2002 - Bastian
$PMF_LANG['ad_log_lger'] = 'Usuario o contraseña inválido.';
$PMF_LANG['ad_log_sess'] = 'Sesión expirada.';
$PMF_LANG['ad_log_edit'] = 'Formulario <i>Editar Usuario</i> del usuario: ';
$PMF_LANG['ad_log_crea'] = 'Formulario <i>Nuevo Artículo</i>.';
$PMF_LANG['ad_log_crsa'] = 'Nueva entrada creada.';
$PMF_LANG['ad_log_ussa'] = 'Datos actualizados del usuario: ';
$PMF_LANG['ad_log_usde'] = 'Usuario eliminado: ';
$PMF_LANG['ad_log_beed'] = 'Formulario Edición del artículo: ';
$PMF_LANG['ad_log_bede'] = 'Eliminada la entrada: ';

$PMF_LANG['ad_start_visits'] = 'Visitas';
$PMF_LANG['ad_start_articles'] = 'FAQs';
$PMF_LANG['ad_start_comments'] = 'Comentarios';

// Añadido v1.1 - 30.01.2002 - Bastian
$PMF_LANG['ad_categ_paste'] = 'pegar';
$PMF_LANG['ad_categ_cut'] = 'cortar';
$PMF_LANG['ad_categ_copy'] = 'copiar';
$PMF_LANG['ad_categ_process'] = 'Procesando categorías...';

// Añadido v1.1.4 - 07.05.2002 - Thorsten
$PMF_LANG['err_NotAuth'] = '<strong>No tienes autorización.</strong>';

// Añadido v1.2.3 - 29.11.2002 - Thorsten
$PMF_LANG['msgPreviusPage'] = 'página anterior';
$PMF_LANG['msgNextPage'] = 'página siguiente';
$PMF_LANG['msgPageDoublePoint'] = 'Página: ';
$PMF_LANG['msgMainCategory'] = 'Categoría Principal';

// Añadido v1.2.4 - 30.01.2003 - Thorsten
$PMF_LANG['ad_passwdsuc'] = '¡Contraseña cambiada con éxito!';

// Añadido v1.3.0 - 04.03.2003 - Thorsten
$PMF_LANG['ad_xml_gen'] = 'Exportar como archivo XML';
$PMF_LANG['ad_entry_locale'] = 'Idioma';
$PMF_LANG['msgLanguageSubmit'] = 'Cambiar idioma';

// Añadido v1.3.1 - 29.04.2003 - Thorsten
$PMF_LANG['ad_attach_4'] = 'El archivo adjunto debe ser menor de %s Bytes.';
$PMF_LANG['ad_menu_export'] = 'Exportar FAQ';

$PMF_LANG['rightsLanguage']['add_user'] = 'Añadir usuario';
$PMF_LANG['rightsLanguage']['edit_user'] = 'Editar usuario';
$PMF_LANG['rightsLanguage']['delete_user'] = 'Eliminar usuario';
$PMF_LANG['rightsLanguage']['add_faq'] = 'Añadir FAQ';
$PMF_LANG['rightsLanguage']['edit_faq'] = 'Editar FAQ';
$PMF_LANG['rightsLanguage']['delete_faq'] = 'Eliminar FAQ';
$PMF_LANG['rightsLanguage']['viewlog'] = 'Ver log';
$PMF_LANG['rightsLanguage']['adminlog'] = 'Ver admin-log';
$PMF_LANG['rightsLanguage']['delcomment'] = 'Eliminar comentario';
$PMF_LANG['rightsLanguage']['addnews'] = 'Añadir noticias';
$PMF_LANG['rightsLanguage']['editnews'] = 'Editar noticias';
$PMF_LANG['rightsLanguage']['delnews'] = 'Eliminar noticias';
$PMF_LANG['rightsLanguage']['addcateg'] = 'Añadir categoría';
$PMF_LANG['rightsLanguage']['editcateg'] = 'Editar categoría';
$PMF_LANG['rightsLanguage']['delcateg'] = 'Eliminar categoría';
$PMF_LANG['rightsLanguage']['passwd'] = 'Cambiar contraseña';
$PMF_LANG['rightsLanguage']['editconfig'] = 'Editar configuración';
$PMF_LANG['rightsLanguage']['addatt'] = 'Añadir adjuntos';
$PMF_LANG['rightsLanguage']['delatt'] = 'Eliminar adjuntos';
$PMF_LANG['rightsLanguage']['backup'] = 'Crear backup';
$PMF_LANG['rightsLanguage']['restore'] = 'Restaurar backup';
$PMF_LANG['rightsLanguage']['delquestion'] = 'Eliminar preguntas abiertas';
$PMF_LANG['rightsLanguage']['changebtrevs'] = 'Editar revisiones';

$PMF_LANG['msgAttachedFiles'] = 'Archivos adjuntos:';

// Añadido v1.3.3 - 27.05.2003 - Thorsten
$PMF_LANG['ad_user_action'] = 'Acción';
$PMF_LANG['ad_entry_email'] = 'e-mail:';
$PMF_LANG['ad_entry_allowComments'] = 'Permitir comentarios';
$PMF_LANG['msgWriteNoComment'] = 'No se pueden hacer comentarios';
$PMF_LANG['ad_user_realname'] = 'Nombre real:';
$PMF_LANG['ad_export_generate_pdf'] = 'Exportar como PDF';
$PMF_LANG['ad_export_full_faq'] = 'Tu FAQ como archivo PDF: ';
$PMF_LANG['err_bannedIP'] = 'Tu IP ha sido bloqueada.';
$PMF_LANG['err_SaveQuestion'] = '¡Los campos obligatorios son <strong>Nombre</strong>, <strong>e-mail</strong> y <strong>Pregunta</strong>!';

// Añadido v1.4.0 - 2003-12-04 por Thorsten / Mathias
$LANG_CONF['main.language'] = [0 => "select", 1 => "Idioma"];
$LANG_CONF['main.languageDetection'] = [0 => "checkbox", 1 => "Habilitar reconocimiento automático de idioma"];
$LANG_CONF['main.titleFAQ'] = [0 => "input", 1 => "Título del FAQ"];
$LANG_CONF['main.currentVersion'] = [0 => "print", 1 => "Versión phpMyFAQ"];
$LANG_CONF['main.metaDescription'] = [0 => "input", 1 => "Descripción de la página"];
$LANG_CONF['main.metaKeywords'] = [0 => "input", 1 => "Palabras clave para motores de búsqueda"];
$LANG_CONF['main.metaPublisher'] = [0 => "input", 1 => "Nombre del editor"];
$LANG_CONF['main.administrationMail'] = [0 => "input", 1 => "e-mail del administrador"];
$LANG_CONF['main.contactInformations'] = [0 => "area", 1 => "Informaciones de contacto"];
$LANG_CONF['main.send2friendText'] = [0 => "area", 1 => "Texto para la página de enviar a un amigo"];
$LANG_CONF['records.maxAttachmentSize'] = [0 => "input", 1 => "Tamaño máximo de los adjuntos en Bytes (máx. %sByte)"];
$LANG_CONF['records.disableAttachments'] = [0 => "checkbox", 1 => "Mostrar adjuntos bajo las entradas"];
$LANG_CONF['main.enableUserTracking'] = [0 => "checkbox", 1 => "¿Rastreo de usuario habilitado?"];
$LANG_CONF['main.enableAdminLog'] = [0 => "checkbox", 1 => "¿Habilitar Adminlog?"];
$LANG_CONF["main.enableCategoryRestrictions"] = array(0 => "checkbox", 1 => "Habilitar las restricciones de categoría");
$LANG_CONF['security.ipCheck'] = [0 => "checkbox", 1 => "¿Debería usarse la IP para el control en el área de administración?"];
$LANG_CONF['records.numberOfRecordsPerPage'] = [0 => "input", 1 => "Número de FAQs por página"];
$LANG_CONF['records.numberOfShownNewsEntries'] = [0 => "input", 1 => "Número de noticias mostradas"];
$LANG_CONF['security.bannedIPs'] = [0 => "area", 1 => "IPs bloqueadas (sepárelas con espacios)"];
$LANG_CONF['main.enableRewriteRules'] = [0 => "checkbox", 1 => "Activar URLs amigables para SEO"];
$LANG_CONF['ldap.ldapSupport'] = [0 => "checkbox", 1 => "Activar soporte LDAP"];
$LANG_CONF['main.referenceURL'] = [0 => "input", 1 => "URL de la FAQ (p.ej. https://www.example.org/faq/)"];
$LANG_CONF['main.urlValidateInterval'] = [0 => "input", 1 => "Tiempo entre las comprobaciones de enlace de Ajax (en segundos)"];
$LANG_CONF['records.enableVisibilityQuestions'] = [0 => "checkbox", 1 => "Visibilidad de nuevas preguntas"];
$LANG_CONF['security.permLevel'] = [0 => "select", 1 => "Nivel de acceso"];

$PMF_LANG['ad_categ_new_main_cat'] = 'Como nueva categoría principal';
$PMF_LANG['ad_categ_paste_error'] = 'Esta categoría no puede ser insertada aquí.';
$PMF_LANG['ad_categ_move'] = 'Mover categoría';
$PMF_LANG['ad_categ_lang'] = 'Idioma';
$PMF_LANG['ad_categ_desc'] = 'Descripción';
$PMF_LANG['ad_categ_change'] = 'Intercambio con';

$PMF_LANG['lostPassword'] = '¿Olvidó su contraseña?.';
$PMF_LANG['lostpwd_err_1'] = 'Error: Usuario y e-mail no encontrados.';
$PMF_LANG['lostpwd_err_2'] = '¡Error: Entrada incorrecta!';
$PMF_LANG['lostpwd_text_1'] = 'Gracias por solicitar la información de su cuenta.';
$PMF_LANG['lostpwd_text_2'] = 'Por favor, elija una nueva contraseña en el área de administración de la FAQ.';
$PMF_LANG['lostpwd_mail_okay'] = 'E-mail enviado.';

$PMF_LANG['ad_xmlrpc_button'] = 'Obtener la versión actual de phpMyFAQ en línea';
$PMF_LANG['ad_xmlrpc_latest'] = 'Versión actual en';

// añadido v1.5.0 - 2005-07-31 por Thorsten
$PMF_LANG['ad_categ_select'] = 'Idioma de la categoría';

// añadido v1.5.1 - 2005-09-06 por Thorsten
$PMF_LANG['msgSitemap'] = 'Mapa del sitio';

// añadido v1.5.2 - 2005-09-23 por Lars
$PMF_LANG['err_inactiveArticle'] = 'Artículo en revisión y no puede ser mostrado.';
$PMF_LANG['msgArticleCategories'] = 'Categorías para este artículo';

// Añadido v1.6.0 - 2006-02-02 por Thorsten
$PMF_LANG['ad_entry_solution_id'] = 'ID único';
$PMF_LANG['ad_entry_faq_record'] = 'Entrada en la FAQ';
$PMF_LANG['ad_entry_new_revision'] = 'Nueva revisión?';
$PMF_LANG['ad_entry_record_administration'] = 'Administración de FAQ';
$PMF_LANG['ad_entry_revision'] = 'Revisión';
$PMF_LANG['ad_changerev'] = 'Elegir Revisión';
$PMF_LANG['msgCaptcha'] = 'Por favor, introduzca el código del captcha';
$PMF_LANG['msgSelectCategories'] = 'Buscar en ...';
$PMF_LANG['msgAllCategories'] = '... todas las categorías';
$PMF_LANG['ad_you_should_update'] = 'Tu instalación de phpMyFAQ está desactualizada. Deberías actualizarte a la última versión.';
$PMF_LANG['msgAdvancedSearch'] = 'Búsqueda avanzada';

// added v1.6.1 - 2006-04-25 by Matteo and Thorsten
$PMF_LANG['spamControlCenter'] = 'Protección contra Spam';
$LANG_CONF['spam.EnableSafeEmail'] = array(0 => "checkbox", 1 => "Mostra email de usuario en modo seguro");
$LANG_CONF['spam.CheckBannedWords'] = array(0 => "checkbox", 1 => "Habilitar lista palabras censuradas");
$LANG_CONF['spam.EnableCatpchaCode'] = array(0 => "checkbox", 1 => "Mostrar gráfico Captcha");
$PMF_LANG['ad_session_expiring'] = 'La sesión terminará en %d minutos ¿Quieres seguir trabajando?';

// added v1.6.2 - 2006-06-13 by Matteo
$PMF_LANG['ad_stat_management'] = 'Administrador de sesiones';
$PMF_LANG['ad_stat_choose'] = 'Selecciona el mes';
$PMF_LANG['ad_stat_delete'] = 'Eliminar inmediatamente las sesiones seleccionadas';

// added v2.0.0 - 2005-09-15 by Thorsten and by Minoru
$PMF_LANG['ad_menu_glossary'] = 'Glosario';
$PMF_LANG['ad_glossary_add'] = 'Añadir entrada del glosario';
$PMF_LANG['ad_glossary_item'] = 'Elemento';
$PMF_LANG['ad_glossary_definition'] = 'Definición';
$PMF_LANG['ad_glossary_save'] = 'Guardar entrada';
$PMF_LANG['ad_glossary_save_success'] = 'Entrada del glosario guardada sin errores!';
$PMF_LANG['ad_glossary_save_error'] = 'La entrada del glosario no se guardó porque se produjo un error.';
$PMF_LANG['ad_glossary_edit'] = 'Editar entrada';
$PMF_LANG['ad_glossary_update_success'] = 'La entrada del glosario se actualizó con éxito.';
$PMF_LANG['ad_glossary_update_error'] = 'La entrada del glosario no se actualizó porque se produjo un error.';
$PMF_LANG['ad_glossary_delete'] = 'Eliminar entrada';
$PMF_LANG['ad_glossary_delete_success'] = 'La entrada del glosario fue eliminada con éxito.';
$PMF_LANG['ad_glossary_delete_error'] = 'La entrada del glosario no fue eliminada porque se produjo un error.';
$PMF_LANG['ad_linkcheck_noReferenceURL'] = 'Comprobación automática de enlaces desactivada (la URL base no está configurada)';
$PMF_LANG['ad_linkcheck_noAllowUrlOpen'] = 'Comprobación automática de enlace desactivada (la opción de PHP allow_url_fopen no está activada)';
$PMF_LANG['ad_linkcheck_checkResult'] = 'Resultado de la comprobación automática del enlace';
$PMF_LANG['ad_linkcheck_checkSuccess'] = 'OK';
$PMF_LANG['ad_linkcheck_checkFailed'] = 'Falló';
$PMF_LANG['ad_linkcheck_failReason'] = 'Razones';
$PMF_LANG['ad_linkcheck_noLinksFound'] = 'No se han encontrado URLs para su verificación';
$PMF_LANG['ad_linkcheck_searchbadonly'] = 'Sólo con vínculos inaccesibles';
$PMF_LANG['ad_linkcheck_infoReason'] = 'Más información';
$PMF_LANG['ad_linkcheck_openurl_infoprefix'] = 'Encontrado durante el testeo <strong>%s</strong>: ';
$PMF_LANG['ad_linkcheck_openurl_notready'] = 'La comprobación del enlace no está lista.';
$PMF_LANG['ad_linkcheck_openurl_maxredirect'] = 'Número máximo de redirecciones <strong>%d</strong> excedido.';
$PMF_LANG['ad_linkcheck_openurl_urlisblank'] = 'URL vacía resuelta.';
$PMF_LANG['ad_linkcheck_openurl_tooslow'] = 'El servidor <strong>%s</strong> es lento o no responde.';
$PMF_LANG['ad_linkcheck_openurl_nodns'] = 'El servidor <strong>%s</strong> es lento o no tiene registro DNS.';
$PMF_LANG['ad_linkcheck_openurl_redirected'] = 'La URL fue redirigida a <strong>%s</strong>.';
$PMF_LANG['ad_linkcheck_openurl_ambiguous'] = 'Estado HTTP poco claro <strong>%s</strong> encontrado.';
$PMF_LANG['ad_linkcheck_openurl_not_allowed'] = 'El método <em>HEAD</em> no está soportado por el servidor <strong>%s</strong>, métodos permitidos: <strong>%s</strong>.';
$PMF_LANG['ad_linkcheck_openurl_not_found'] = 'Este recurso no se puede encontrar en el servidor <strong>%s</strong>.';
$PMF_LANG['ad_linkcheck_protocol_unsupported'] = 'El protocolo %s no está soportado para la comprobación automática de enlaces.';
$PMF_LANG['msgNewQuestionVisible'] = 'Sin embargo, el administrador debe liberarlos primero.';
$PMF_LANG['msgQuestionsWaiting'] = 'Esperando la aprobación del administrador';
$PMF_LANG['ad_entry_visibility'] = 'visible';

// added v2.0.0 - 2006-01-02 by Lars
$PMF_LANG['ad_user_error_password'] = 'Por favor, introduzca una contraseña. ';
$PMF_LANG['ad_user_error_passwordsDontMatch'] = 'Las contraseñas no coinciden. ';
$PMF_LANG['ad_user_error_loginInvalid'] = 'El usuario seleccionado es inválido.';
$PMF_LANG['ad_user_error_noEmail'] = 'Por favor, introduzca una dirección de e-mail correcta. ';
$PMF_LANG['ad_user_error_noRealName'] = 'Por favor, ingrese su nombre. ';
$PMF_LANG['ad_user_error_delete'] = 'La cuenta de usuario no puede ser eliminada. ';
$PMF_LANG['ad_user_error_noId'] = 'No hay ID seleccionado. ';
$PMF_LANG['ad_user_error_protectedAccount'] = 'La cuenta del usuario está protegida. ';
$PMF_LANG['ad_user_deleteUser'] = 'Eliminar usuario';
$PMF_LANG['ad_user_status'] = 'Estado:';
$PMF_LANG['ad_user_lastModified'] = 'Última Modificación:';
$PMF_LANG['ad_gen_cancel'] = 'Cancelar';
$PMF_LANG['rightsLanguage']['addglossary'] = 'Añadir entradas del diccionario';
$PMF_LANG['rightsLanguage']['editglossary'] = 'Editar entradas del diccionario';
$PMF_LANG['rightsLanguage']['delglossary'] = 'Eliminar entradas del diccionario';
$PMF_LANG['ad_menu_group_administration'] = 'Grupos';
$PMF_LANG['ad_user_loggedin'] = 'Estás conectado como ';
$PMF_LANG['ad_group_details'] = 'Detalles del grupo';
$PMF_LANG['ad_group_add'] = 'Añadir grupo';
$PMF_LANG['ad_group_add_link'] = 'Añadir grupo';
$PMF_LANG['ad_group_name'] = 'Nombre';
$PMF_LANG['ad_group_description'] = 'Descripción';
$PMF_LANG['ad_group_autoJoin'] = 'Entrada automática';
$PMF_LANG['ad_group_suc'] = 'El grupo se ha añadido <strong>correctamente</strong>.';
$PMF_LANG['ad_group_error_noName'] = 'Por favor, introduzca un nombre para el grupo.';
$PMF_LANG['ad_group_error_delete'] = 'El grupo no pudo ser eliminado.';
$PMF_LANG['ad_group_deleted'] = 'El grupo fue eliminado con éxito.';
$PMF_LANG['ad_group_deleteGroup'] = 'Eliminar grupo';
$PMF_LANG['ad_group_deleteQuestion'] = '¿Estás seguro de que quieres eliminar este grupo?';
$PMF_LANG['ad_user_uncheckall'] = 'Desmarcar Todos';
$PMF_LANG['ad_group_membership'] = 'Miembros del grupo';
$PMF_LANG['ad_group_members'] = 'Miembros';
$PMF_LANG['ad_group_addMember'] = '+';
$PMF_LANG['ad_group_removeMember'] = '-';

// added v2.0.0 - 2006-07-20 by Matteo
$PMF_LANG['ad_export_which_cat'] = 'Limitar el contenido exportado de las FAQ (opcional)';
$PMF_LANG['ad_export_cat_downwards'] = '¿Incluir subcategorías?';
$PMF_LANG['ad_export_type'] = 'Formato de exportación';
$PMF_LANG['ad_export_type_choose'] = 'Por favor, seleccione uno de los formatos soportados';
$PMF_LANG['ad_export_download_view'] = '¿Descargar o ver en línea?';
$PMF_LANG['ad_export_download'] = 'Descargar';
$PMF_LANG['ad_export_view'] = 'ver en línea';
$PMF_LANG['ad_export_gen_xhtml'] = 'Exportar como archivo XHTML';
$PMF_LANG['ad_export_gen_docbook'] = 'Crear un archivo DocBook XML';

// added v2.0.0 - 2006-07-22 by Matteo
$PMF_LANG['ad_news_data'] = 'Mensaje';
$PMF_LANG['ad_news_author_name'] = 'Autor';
$PMF_LANG['ad_news_author_email'] = 'E-mail del autor';
$PMF_LANG['ad_news_set_active'] = 'Activar';
$PMF_LANG['ad_news_allowComments'] = 'Permitir comentarios';
$PMF_LANG['ad_news_expiration_window'] = 'Fecha de caducidad del mensaje (opcional)';
$PMF_LANG['ad_news_from'] = 'de';
$PMF_LANG['ad_news_to'] = 'a';
$PMF_LANG['ad_news_insertfail'] = 'Se ha producido un error al guardar en la base de datos.';
$PMF_LANG['ad_news_updatefail'] = 'Se ha producido un error al actualizar la entrada en la base de datos.';
$PMF_LANG['newsShowCurrent'] = 'Ver noticias actuales.';
$PMF_LANG['newsShowArchive'] = 'Ver noticias archivadas.';
$PMF_LANG['newsArchive'] = ' Archivo de noticias';
$PMF_LANG['newsWriteComment'] = 'Comentar esta entrada';
$PMF_LANG['newsCommentDate'] = 'Escrito en: ';

// added v2.0.0 - 2006-07-29 by Matteo & Thorsten
$PMF_LANG['ad_record_expiration_window'] = 'Fecha de caducidad de la entrada (opcional)';
$PMF_LANG['admin_mainmenu_home'] = 'Tablero de mandos';
$PMF_LANG['admin_mainmenu_users'] = 'Usuarios';
$PMF_LANG['admin_mainmenu_content'] = 'Contenido';
$PMF_LANG['admin_mainmenu_statistics'] = 'Estadísticas';
$PMF_LANG['admin_mainmenu_exports'] = 'Exportar';
$PMF_LANG['admin_mainmenu_backup'] = "Backup";
$PMF_LANG['admin_mainmenu_configuration'] = 'Configuración';
$PMF_LANG['admin_mainmenu_logout'] = 'Cerrar sesión';

// added v2.0.0 - 2006-08-15 by Thorsten
$PMF_LANG['ad_categ_owner'] = 'Propietario de la categoría';
$PMF_LANG['adminSection'] = 'Administración';
$PMF_LANG['err_expiredArticle'] = 'Esta FAQ ha expirado y no puede ser mostrada';
$PMF_LANG['err_expiredNews'] = 'Este mensaje ha expirado y no puede ser mostrado';
$PMF_LANG['err_inactiveNews'] = 'Este mensaje está siendo revisado y no puede ser mostrado';
$PMF_LANG['msgSearchOnAllLanguages'] = 'buscar en todos los idiomas';
$PMF_LANG['ad_entry_tags'] = 'Etiquetas';
$PMF_LANG['msg_tags'] = 'Etiquetas';

// added v2.0.0 - 2006-09-03 by Matteo
$PMF_LANG['ad_linkcheck_feedback_url-batch1'] = 'comprobando...';
$PMF_LANG['ad_linkcheck_feedback_url-batch2'] = 'comprobando...';
$PMF_LANG['ad_linkcheck_feedback_url-batch3'] = 'comprobando...';
$PMF_LANG['ad_linkcheck_feedback_url-checking'] = 'comprobando...';
$PMF_LANG['ad_linkcheck_feedback_url-disabled'] = 'deshabilitado';
$PMF_LANG['ad_linkcheck_feedback_url-linkbad'] = 'Los enlaces no están bien';
$PMF_LANG['ad_linkcheck_feedback_url-linkok'] = 'Enlaces OK';
$PMF_LANG['ad_linkcheck_feedback_url-noaccess'] = '¡No hay acceso posible!';
$PMF_LANG['ad_linkcheck_feedback_url-noajax'] = 'No hay soporte AJAX disponible';
$PMF_LANG['ad_linkcheck_feedback_url-nolinks'] = 'No se han encontrado enlaces';
$PMF_LANG['ad_linkcheck_feedback_url-noscript'] = 'No hay soporte para el script disponible';

// added v2.0.0 - 2006-09-02 by Thomas
$PMF_LANG['msg_related_articles'] = 'Artículos relacionados';
$LANG_CONF['records.numberOfRelatedArticles'] = array(0 => "input", 1 => "Número de FAQs relacionadas");

// added v2.0.0 - 2006-09-09 by Rudi
$PMF_LANG['ad_categ_trans_1'] = 'Traducir';
$PMF_LANG['ad_categ_trans_2'] = 'Categoría';
$PMF_LANG['ad_categ_translatecateg'] = 'Guardar la traducción';
$PMF_LANG['ad_categ_translate'] = 'Traducir';
$PMF_LANG['ad_categ_transalready'] = 'Ya traducido en: ';
$PMF_LANG['ad_categ_deletealllang'] = '¿Eliminar en todos los idiomas?';
$PMF_LANG['ad_categ_deletethislang'] = '¿Eliminar sólo en este idioma?';
$PMF_LANG['ad_categ_translated'] = 'La categoría se ha traducido.';

// added v2.0.0 - 2006-09-21 by Rudi
$PMF_LANG['ad_categ_show'] = 'Resumen';
$PMF_LANG['ad_menu_categ_structure'] = 'Resumen de las categorías con sus idiomas';

// added v2.0.0 - 2006-09-26 by Thorsten
$PMF_LANG['ad_entry_userpermission'] = 'Permisos de usuario';
$PMF_LANG['ad_entry_grouppermission'] = 'Permisos del grupo';
$PMF_LANG['ad_entry_all_users'] = 'Acceso para todos los usuarios';
$PMF_LANG['ad_entry_restricted_users'] = 'Acceso sólo para';
$PMF_LANG['ad_entry_all_groups'] = 'Acceso para todos los grupos';
$PMF_LANG['ad_entry_restricted_groups'] = 'Acceso sólo para';
$PMF_LANG['ad_session_expiration'] = 'La sesión expira en';
$PMF_LANG['ad_user_active'] = 'activo';
$PMF_LANG['ad_user_blocked'] = 'bloqueado';
$PMF_LANG['ad_user_protected'] = 'protegido';

// added v2.0.0 - 2006-10-07 by Matteo
$PMF_LANG['ad_entry_intlink'] = 'Selecciona un registro de la FAQ para insertarlo como un enlace...';

// added 2.0.0 - 2006-10-10 by Rudi
$PMF_LANG['ad_categ_paste2'] = 'Pegar después';
$PMF_LANG['ad_categ_remark_move'] = 'Mover dos categorías sólo es posible dentro del mismo nivel.';
$PMF_LANG['ad_categ_remark_overview'] = 'El orden correcto de las categorías se muestra cuando todas las categorías se definen en el idioma actual (primera columna).';

// added v2.0.0 - 2006-10-15 by Matteo
$PMF_LANG['msgUsersOnline'] = '%d usuarios anónimos y %d registrados';
$PMF_LANG['ad_adminlog_del_older_30d'] = 'Eliminación automática de registros de más de 30 días';
$PMF_LANG['ad_adminlog_delete_success'] = 'Los viejos archivos de registro fueron eliminados con éxito.';
$PMF_LANG['ad_adminlog_delete_failure'] = 'No se eliminaron registros porque se produjo un error.';

// added 2.0.0 - 2006-11-19 by Thorsten
$PMF_LANG['opensearch_plugin_install'] = 'Instalar el plugin de búsqueda';
$PMF_LANG['ad_quicklinks'] = 'Enlaces rápidos';
$PMF_LANG['ad_quick_category'] = 'Añadir nueva categoría';
$PMF_LANG['ad_quick_record'] = 'Añadir nueva FAQ';
$PMF_LANG['ad_quick_user'] = 'Añadir nuevo usuario';
$PMF_LANG['ad_quick_group'] = 'Añadir nuevo grupo de usuarios';

// added v2.0.0 - 2006-12-30 by Matteo
$PMF_LANG['msgNewTranslationHeader'] = 'Sugerir traducción';
$PMF_LANG['msgNewTranslationAddon'] = 'La traducción no aparecerá inmediatamente, pero será revisada por nosotros antes de su publicación. Los campos obligatorios son <strong>Nombre</strong>, <strong>E-mail</strong>, <strong>Categoría</fuerte>, <strong>Pregunta</strong> y <strong>Respuesta</strong>. Por favor, separe las palabras clave con espacios solamente.';
$PMF_LANG['msgNewTransSourcePane'] = 'Contribución original';
$PMF_LANG['msgNewTranslationPane'] = 'Traducción';
$PMF_LANG['msgNewTranslationName'] = 'Nombre';
$PMF_LANG['msgNewTranslationMail'] = 'E-mail';
$PMF_LANG['msgNewTranslationKeywords'] = 'Palabras calve';
$PMF_LANG['msgNewTranslationSubmit'] = 'Envía tu propuesta';
$PMF_LANG['msgTranslate'] = 'Traducción sugerida';
$PMF_LANG['msgTranslateSubmit'] = 'Iniciar la traducción...';
$PMF_LANG['msgNewTranslationThanks'] = '¡Muchas gracias por la sugerencia de traducción!';

// added v2.0.0 - 2007-02-27 by Matteo
$PMF_LANG['rightsLanguage']['addgroup'] = 'Añadir grupos';
$PMF_LANG['rightsLanguage']['editgroup'] = 'Editar grupos';
$PMF_LANG['rightsLanguage']['delgroup'] = 'Eliminar grupos';

// added v2.0.0 - 2007-02-27 by Thorsten
$PMF_LANG['ad_news_link_parent'] = 'El enlace se abre en la misma ventana';

// added v2.0.0 - 2007-03-04 by Thorsten
$PMF_LANG['ad_menu_comments'] = 'Comentarios';
$PMF_LANG['ad_comment_administration'] = 'Administración de comentarios';
$PMF_LANG['ad_comment_faqs'] = 'Comentarios en registros de FAQ';
$PMF_LANG['ad_comment_news'] = 'Comentarios en registros de noticias:';
$PMF_LANG['msgPDF'] = 'Versión PDF';
$PMF_LANG['ad_groups'] = 'Grupos';

// added v2.0.0 - 2007-03-10 by Thorsten
$LANG_CONF['records.orderby'] = array(0 => 'select', 1 => 'Ordenar (por propiedad)');
$LANG_CONF['records.sortby'] = array(0 => 'select', 1 => 'Ordenar (descendente/ascendente)');
$PMF_LANG['ad_conf_order_id'] = 'ID<br>(Estándar)';
$PMF_LANG['ad_conf_order_thema'] = 'Pregunta';
$PMF_LANG['ad_conf_order_visits'] = 'Número de visitas';
$PMF_LANG['ad_conf_order_updated'] = 'Fecha';
$PMF_LANG['ad_conf_order_author'] = 'Autor';
$PMF_LANG['ad_conf_desc'] = 'descendiente';
$PMF_LANG['ad_conf_asc'] = 'ascendente';
$PMF_LANG['mainControlCenter'] = 'General';
$PMF_LANG['recordsControlCenter'] = 'FAQs';

// added v2.0.0 - 2007-03-29 by Thorsten
$LANG_CONF['records.defaultActivation'] = array(0 => "checkbox", 1 => "¿Las nuevas FAQs son visibles de inmediato?");
$LANG_CONF['records.defaultAllowComments'] = array(0 => "checkbox", 1 => "¿Se permiten comentarios en las FAQs?");

// added v2.0.0 - 2007-04-04 by Thorsten
$PMF_LANG['msgAllCatArticles'] = 'FAQs en esta categoría';
$PMF_LANG['msgTagSearch'] = 'FAQs con las mismas etiquetas';
$PMF_LANG['ad_pmf_info'] = 'Información de phpMyFAQ';
$PMF_LANG['ad_online_info'] = 'Comprobación de la versión online';
$PMF_LANG['ad_system_info'] = 'Información del sistema';

// added 2.5.0-alpha - 2008-01-25 by Elger
$PMF_LANG['msgRegisterUser'] = 'Registrar';
$PMF_LANG['ad_user_loginname'] = 'Usuario:';
$PMF_LANG['errorRegistration'] = '¡Este campo debe ser rellenado!';
$PMF_LANG['submitRegister'] = 'Registrar usuario';
$PMF_LANG['msgUserData'] = 'Información de usuario requerida para el registro';
$PMF_LANG['captchaError'] = '¡Por favor, introduzca los datos correctos del CAPTCHA!';
$PMF_LANG['msgRegError'] = 'Por favor, corrija los siguientes errores';
$PMF_LANG['successMessage'] = '¡Registro correcto. En breve recibirás un correo electrónico con sus datos';
$PMF_LANG['msgRegThankYou'] = 'Gracias por tu registro';
$PMF_LANG['emailRegSubject'] = '[%sitename%] Registro: nuevo usuario';

// added 2.5.0-alpha2 - 2009-01-24 by Thorsten
$PMF_LANG['msgMostPopularSearches'] = 'Los términos de búsqueda más populares son';
$LANG_CONF['main.enableWysiwygEditor'] = array(0 => 'checkbox', 1 => 'Habilitar editor WYSIWYG');

// added 2.5.0-beta - 2009-03-30 by Anatoliy
$PMF_LANG['ad_menu_searchstats'] = 'Estadística de búsqueda';
$PMF_LANG['ad_searchstats_search_term'] = 'Términos de búsqueda';
$PMF_LANG['ad_searchstats_search_term_count'] = 'Número';
$PMF_LANG['ad_searchstats_search_term_lang'] = 'Idioma';
$PMF_LANG['ad_searchstats_search_term_percentage'] = 'Porcentaje';

// added 2.5.0-beta - 2009-03-31 by Anatoliy
$PMF_LANG['ad_record_sticky'] = 'Pegajoso';
$PMF_LANG['ad_entry_sticky'] = 'FAQ pegajosa';
$PMF_LANG['stickyRecordsHeader'] = 'FAQs pegajosas';

// added 2.5.0-beta - 2009-04-01 by Anatoliy
$PMF_LANG['ad_menu_stopwordsconfig'] = 'Palabras vacías';
$PMF_LANG['ad_config_stopword_input'] = 'Agregar nueva palabra vacía';

// added 2.5.0-beta - 2009-04-06 by Anatoliy
$PMF_LANG['msgSendMailDespiteEverything'] = 'No, no se encontró ninguna respuesta que coincida.';
$PMF_LANG['msgSendMailIfNothingIsFound'] = '¿La respuesta que buscas está en la lista anterior?';

// added 2.5.0-RC - 2009-05-11 by Anatoliy & Thorsten
$PMF_LANG['msgChooseLanguageToTranslate'] = 'Por favor, seleccione el idioma a traducir';
$PMF_LANG['msgLangDirIsntWritable'] = 'El directorio con los archivos de traducción no es escribible.';
$PMF_LANG['ad_menu_translations'] = 'Traducción';
$PMF_LANG['ad_start_notactive'] = 'Esperando activación';

// added 2.5.0-RC - 2009-05-20 by Anatoliy
$PMF_LANG['msgTransToolAddNewTranslation'] = 'Añadir nueva traducción';
$PMF_LANG['msgTransToolLanguage'] = 'Idioma';
$PMF_LANG['msgTransToolActions'] = 'Acciones';
$PMF_LANG['msgTransToolWritable'] = 'Escribible';
$PMF_LANG['msgEdit'] = 'Editar';
$PMF_LANG['msgDelete'] = 'Eliminar';
$PMF_LANG['msgYes'] = 'sí';
$PMF_LANG['msgNo'] = 'no';
$PMF_LANG['msgTransToolSureDeleteFile'] = '¿Realmente quieres eliminar este archivo de idioma?';
$PMF_LANG['msgTransToolFileRemoved'] = 'Archivo de idioma eliminado con éxito';
$PMF_LANG['msgTransToolErrorRemovingFile'] = 'Error eliminando el archivo de idioma';
$PMF_LANG['msgVariable'] = 'Variable';
$PMF_LANG['msgCancel'] = 'Cancelar';
$PMF_LANG['msgSave'] = 'Guardar';
$PMF_LANG['msgSaving3Dots'] = 'guardando ...';
$PMF_LANG['msgRemoving3Dots'] = 'eliminando ...';
$PMF_LANG['msgTransToolFileSaved'] = 'Archivo de idioma guardado con éxito';
$PMF_LANG['msgTransToolErrorSavingFile'] = 'Error guardando el archivo de idioma';
$PMF_LANG['msgLanguage'] = 'Idioma';
$PMF_LANG['msgTransToolLanguageCharset'] = 'Juego de caracteres';
$PMF_LANG['msgTransToolLanguageDir'] = 'Dirección de la fuente';
$PMF_LANG['msgTransToolLanguageDesc'] = 'Descripción del idioma';
$PMF_LANG['msgAuthor'] = "Autor";
$PMF_LANG['msgTransToolAddAuthor'] = 'Agregar autor';
$PMF_LANG['msgTransToolCreateTranslation'] = 'Añadir nueva traducción';
$PMF_LANG['msgTransToolTransCreated'] = 'Nueva traducción creada con éxito.';
$PMF_LANG['msgTransToolCouldntCreateTrans'] = 'No se pudo crear una nueva traducción.';
$PMF_LANG['msgAdding3Dots'] = 'agregando ...';
$PMF_LANG['msgTransToolSendToTeam'] = 'Enviar al equipo de phpMyFAQ';
$PMF_LANG['msgSending3Dots'] = 'enviando ...';
$PMF_LANG['msgTransToolFileSent'] = 'El archivo de idioma se envió con éxito al equipo de phpMyFAQ. ¡Muchas gracias por compartirlo!';
$PMF_LANG['msgTransToolErrorSendingFile'] = 'Se ha producido un error al enviar el archivo de idioma.';
$PMF_LANG['msgTransToolPercent'] = 'Porcentaje';

// added 2.5.0-RC3 - 2009-06-23 by Anatoliy
$LANG_CONF['records.attachmentsPath'] = array(0 => 'input', 1 => 'Ruta donde se guardarán los archivos adjuntos.<br><small>La ruta relativa se busca desde Webroot.</small>');

// added 2.5.0-RC3 - 2009-06-24 by Anatoliy
$PMF_LANG['msgAttachmentNotFound'] = 'El archivo no se encontró en el servidor';

// added 2.6.0-alpha - 2009-07-30 by Aurimas Fišeras (plural messages test)
//P.S. "One User online" is also possible, since sprintf just ignores extra args
$PMF_LANG['plmsgUserOnline']['0'] = '%d visitante en línea';
$PMF_LANG['plmsgUserOnline']['1'] = '%d visitante en línea';

// added 2.6.0-alpha - 2009-08-02 by Anatoliy
$LANG_CONF['main.templateSet'] = array(0 => "select", 1 => "Plantilla seleccionada");

// added 2.6.0-alpha - 2009-08-16 by Aurimas Fišeras
$PMF_LANG['msgTransToolRemove'] = 'Eliminar';
$PMF_LANG['msgTransToolLanguageNumberOfPlurals'] = 'Número de formas plurales';
$PMF_LANG['msgTransToolLanguageOnePlural'] = 'Este lenguaje tiene una sola forma plural';
$PMF_LANG['msgTransToolLanguagePluralNotSet'] = 'Para %s, el soporte de formas plurales está desactivado (nplurales no establecidos)';

// added 2.6.0-alpha - 2009-08-16 by Aurimas Fišeras - Plural messages
$PMF_LANG['plmsgHomeArticlesOnline']['0'] = 'Hay %d FAQ online.';
$PMF_LANG['plmsgHomeArticlesOnline']['1'] = 'Hay %d FAQs online.';
$PMF_LANG['plmsgViews']['0'] = '%dx vista';
$PMF_LANG['plmsgViews']['1'] = '%dx vistas';

// added 2.6.0-alpha - 2009-08-30 by Aurimas Fišeras - Plural messages
$PMF_LANG['plmsgGuestOnline']['0'] = '%d invitado';
$PMF_LANG['plmsgGuestOnline']['1'] = '%d invitados';
$PMF_LANG['plmsgRegisteredOnline']['0'] = ' y %d registrado';
$PMF_LANG['plmsgRegisteredOnline']['1'] = ' y %d registrados';
$PMF_LANG['plmsgSearchAmount']['0'] = '%d resultado de la búsqueda';
$PMF_LANG['plmsgSearchAmount']['1'] = '%d resultados de la búsqueda';
$PMF_LANG['plmsgPagesTotal']['0'] = ' %d página';
$PMF_LANG['plmsgPagesTotal']['1'] = ' %d páginas';
$PMF_LANG['plmsgVotes']['0'] = '%d voto';
$PMF_LANG['plmsgVotes']['1'] = '%d votos';
$PMF_LANG['plmsgEntries']['0'] = '%d FAQ';
$PMF_LANG['plmsgEntries']['1'] = '%d FAQs';

// added 2.6.0-alpha - 2009-09-06 by Aurimas Fišeras
$PMF_LANG['rightsLanguage']['addtranslation'] = 'Añadir traducción';
$PMF_LANG['rightsLanguage']['edittranslation'] = 'Editar traducción';
$PMF_LANG['rightsLanguage']['deltranslation'] = 'Eliminar traducción';
$PMF_LANG['rightsLanguage']['approverec'] = 'Aprobar registros';

// added 2.6.0-alpha - 2009-09-9 by Anatoliy Belsky
$LANG_CONF['records.enableAttachmentEncryption'] = array(0 => "checkbox", 1 => "Encriptación de archivos adjuntos");
$LANG_CONF['records.defaultAttachmentEncKey'] = array(0 => "input", 1 => 'Clave predeterminada para el cifrado<br/><small style=\"color: red\">¡Advertencia: No la cambie después de habilitar la encriptación!</small>');
//$LANG_CONF['records.attachmentsStorageType'] = array(0 => "select", 1 => "Attachment storage type");
//$PMF_LANG['att_storage_type'][0] = "Filesystem";
//$PMF_LANG['att_storage_type'][1] = "Database";

// added 2.6.0-alpha - 2009-09-06 by Thorsten
$PMF_LANG['ad_menu_upgrade'] = 'Actualizar';
$PMF_LANG['ad_you_shouldnt_update'] = 'Estás usando la versión actual de phpMyFAQ. No es necesaria una actualización.';
$LANG_CONF['security.useSslForLogins'] = array(0 => 'checkbox', 1 => "¿Permitir los inicios de sesión sólo vía SSL/TLS?");
$PMF_LANG['msgSecureSwitch'] = 'Cambiar a modo seguro de inicio de sesión';

// added 2.6.0-alpha - 2009-10-03 by Anatoliy Belsky
$PMF_LANG['msgTransToolNoteFileSaving'] = 'Por favor, ten en cuenta que no escribiremos ningún archivo hasta que hagas clic en el botón Guardar';
$PMF_LANG['msgTransToolPageBufferRecorded'] = 'Página %d del búfer grabada con éxito';
$PMF_LANG['msgTransToolErrorRecordingPageBuffer'] = 'Error grabando página %d del búfer';
$PMF_LANG['msgTransToolRecordingPageBuffer'] = 'Grabando página %d del búfer';

// added 2.6.0-alpha - 2009-11-02 by Anatoliy Belsky
$PMF_LANG['ad_record_active'] = 'activado';

// added 2.6.0-alpha - 2009-11-01 by Anatoliy Belsky
$PMF_LANG['msgAttachmentInvalid'] = 'El archivo adjunto es inválido, por favor, informa al administrador';

// added 2.6.0-alpha - 2009-11-02 by max
$LANG_CONF['search.numberSearchTerms'] = array(0 => 'input', 1 => 'Número de términos de búsqueda más populares');
$LANG_CONF['records.orderingPopularFaqs'] = array(0 => 'select', 1 => 'Orden de FAQs más populares');
$PMF_LANG['list_all_users'] = 'Mostrar todos los usuarios';

$PMF_LANG['records.orderingPopularFaqs.visits'] = 'por número de visitas';
$PMF_LANG['records.orderingPopularFaqs.voting'] = 'por votos de visitantes';

// added 2.6.0-alpha - 2009-11-05 by Thorsten
$PMF_LANG['msgShowHelp'] = 'Por favor, separe los términos con comas.';

// added 2.6.0-RC - 2009-11-30 by Thorsten
$PMF_LANG['msgUpdateFaqDate'] = 'actualizar';
$PMF_LANG['msgKeepFaqDate'] = 'mantener';
$PMF_LANG['msgEditFaqDat'] = 'editar';
$LANG_CONF['main.optionalMailAddress'] = array(0 => 'checkbox', 1 => 'E-mail como campo obligatorio ');

// added v2.6.99 - 2010-11-24 by Gustavo Solt
$LANG_CONF['search.relevance'] = array(0 => 'select', 1 => 'Ordenar por relevancia');
$LANG_CONF['search.enableRelevance'] = array(0 => 'checkbox', 1 => '¿Activar soporte para la relevancia?');
$PMF_LANG['searchControlCenter'] = 'Búsqueda';
$PMF_LANG['search.relevance.thema-content-keywords'] = 'Pregunta - Respuesta - Palabras clave';
$PMF_LANG['search.relevance.thema-keywords-content'] = 'Pregunta - Palabras clave - Respuesta';
$PMF_LANG['search.relevance.content-thema-keywords'] = 'Respuesta - Pregunta - Palabras clave';
$PMF_LANG['search.relevance.content-keywords-thema'] = 'Respuesta - Palabras clave - Pregunta';
$PMF_LANG['search.relevance.keywords-content-thema'] = 'Palabras clave - Respuesta - Pregunta';
$PMF_LANG['search.relevance.keywords-thema-content'] = 'Palabras clave - Pregunta - Respuesta';

// added 2.7.0-alpha - 2010-09-13 by Thorsten
$PMF_LANG['msgLoginUser'] = 'Iniciar sesión';
$PMF_LANG['socialNetworksControlCenter'] = 'Redes sociales';
$LANG_CONF['socialnetworks.enableTwitterSupport'] = array(0 => 'checkbox', 1 => 'Soporte de Twitter');
$LANG_CONF['socialnetworks.twitterConsumerKey'] = array(0 => 'input', 1 => 'Consumer Key de Twitter');
$LANG_CONF['socialnetworks.twitterConsumerSecret'] = array(0 => 'input', 1 => 'Consumer Secret de Twitter');

// added 2.7.0-alpha - 2010-10-14 by Tom Zeithaml
$LANG_CONF['socialnetworks.twitterAccessTokenKey'] = array(0 => 'input', 1 => 'Access Token de Twitter');
$LANG_CONF['socialnetworks.twitterAccessTokenSecret'] = array(0 => 'input', 1 => 'Access TokenSecret de Twitter');

// added 2.7.0-alpha - 2010-12-21 by Anatoliy Belsky
$PMF_LANG['ad_menu_attachments'] = 'Adjuntos';
$PMF_LANG['ad_menu_attachment_admin'] = 'Administración de archivos adjuntos';
$PMF_LANG['msgAttachmentsFilename'] = 'Nombre del archivo';
$PMF_LANG['msgAttachmentsFilesize'] = 'Tamaño del archivo';
$PMF_LANG['msgAttachmentsMimeType'] = 'Tipo MIME';
$PMF_LANG['msgAttachmentsWannaDelete'] = '¿Estás seguro de querer eliminar este archivo adjunto?';
$PMF_LANG['msgAttachmentsDeleted'] = 'Adjunto eliminado <strong>exitosamente</strong>.';

// added v2.7.0-alpha2 - 2011-01-12 by Gustavo Solt
$PMF_LANG['ad_menu_reports'] = 'Informes';
$PMF_LANG['ad_stat_report_fields'] = 'Campos';
$PMF_LANG['ad_stat_report_category'] = 'Categoría';
$PMF_LANG['ad_stat_report_sub_category'] = 'Subcategoría';
$PMF_LANG['ad_stat_report_translations'] = 'Traducciones';
$PMF_LANG['ad_stat_report_language'] = 'Idioma';
$PMF_LANG['ad_stat_report_id'] = 'ID';
$PMF_LANG['ad_stat_report_sticky'] = 'FAQ pegajosa';
$PMF_LANG['ad_stat_report_title'] = 'Pregunta';
$PMF_LANG['ad_stat_report_creation_date'] = 'Fecha';
$PMF_LANG['ad_stat_report_owner'] = 'Autor';
$PMF_LANG['ad_stat_report_last_modified_person'] = 'Último autor';
$PMF_LANG['ad_stat_report_url'] = 'URL';
$PMF_LANG['ad_stat_report_visits'] = 'Visitas';
$PMF_LANG['ad_stat_report_make_report'] = 'Generar informe';
$PMF_LANG['ad_stat_report_make_csv'] = 'Exportación CSV';

// added v2.7.0-alpha2 - 2011-02-05 by Thorsten Rinne
$PMF_LANG['msgRegistration'] = 'Registro de nuevos usuarios';
$PMF_LANG['msgRegistrationCredentials'] = 'Para registrarte, debes introducir tu nombre, nombre de usuario y una dirección de e-mail correcta.';
$PMF_LANG['msgRegistrationNote'] = 'Después de registrarte correctamente, recibirás una respuesta sobre la activación de tu registro.';

// added v2.7.0-beta - 2011-06-13 by Thorsten
$PMF_LANG['ad_entry_changelog_history'] = 'Historial de cambios';

// added v2.7.0-beta2 - 2011-06-22 by Thorsten
$LANG_CONF['security.ssoSupport'] = array(0 => 'checkbox', 1 => 'Soporte Single Sign On ');
$LANG_CONF['security.ssoLogoutRedirect'] = array(0 => 'input', 1 => 'URL del servicio de redirección de Single Sign On al cerrar sesión');
$LANG_CONF['main.dateFormat'] = array(0 => 'input', 1 => 'Formato de fecha (por defecto: Y-m-d H:i)');
$LANG_CONF['security.enableLoginOnly'] = array(0 => 'checkbox', 1 => 'FAQ con seguridad completa');

// added v2.7.0-RC - 2011-08-18 by Thorsten
$PMF_LANG['securityControlCenter'] = 'Seguridad';
$PMF_LANG['ad_search_delsuc'] = 'El término de búsqueda se eliminó exitosamente';
$PMF_LANG['ad_search_delfail'] = 'El término de búsqueda no pudo ser eliminado.';

// added 2.7.1 - 2011-09-30 by Thorsten
$PMF_LANG['msg_about_faq'] = 'Acerca de esta FAQ';
$LANG_CONF['security.useSslOnly'] = array(0 => 'checkbox', 1 => 'Usar FAQ solo con SSL');
$PMF_LANG['msgTableOfContent'] = 'Tabla de Contenidos';

// added 2.7.5 - 2012-03-02 by Thorsten
$PMF_LANG['msgExportAllFaqs'] = 'Guardar FAQ como PDF';
$PMF_LANG['ad_online_verification'] = 'Verificación en línea.';
$PMF_LANG['ad_verification_button'] = 'Verificar la instalación de phpMyFAQ en línea';
$PMF_LANG['ad_verification_notokay'] = 'Esta instalación de phpMyFAQ tiene cambios locales';
$PMF_LANG['ad_verification_okay'] = 'Esta instalación de phpMyFAQ ha sido verificada con éxito.';

// added v2.8.0-alpha - 2011-09-29 by Thorsten
$PMF_LANG['ad_menu_searchfaqs'] = 'Buscar FAQs';

// added v2.8.0-alpha - 2012-01-13 by Peter
$LANG_CONF['records.enableCloseQuestion'] = array(0 => 'checkbox', 1 => '¿Cerrar pregunta abierta después de la respuesta?');
$LANG_CONF['records.enableDeleteQuestion'] = array(0 => 'checkbox', 1 => '¿Eliminar pregunta abierta después de la respuesta?');
$PMF_LANG['msg2answerFAQ'] = 'Contestada';

// added v2.8.0-alpha - 2012-01-16 by Thorsten
$PMF_LANG['headerUserControlPanel'] = 'Panel de Control de Usuario';

// added v2.8.0-alpha2 - 2012-03-15 by Thorsten
$PMF_LANG['rememberMe'] = 'Recordarme';
$PMF_LANG['ad_menu_instances'] = 'FAQ Multi-sites';

// added v2.8.0-alpha2 - 2012-07-07 by Anatoliy
$LANG_CONF['records.autosaveActive'] = array(0 => 'checkbox', 1 => 'Activar auto-guardado automático de FAQ');
$LANG_CONF['records.autosaveSecs'] = array(0 => 'input', 1 => 'Intervalo de auto-guardado en segundos, por defecto 180');

// added v2.8.0-alpha2 - 2012-08-06 by Thorsten
$PMF_LANG['ad_record_inactive'] = 'FAQs inactivas';
$LANG_CONF['main.maintenanceMode'] = [0 => 'checkbox', 1 => 'FAQ en modo de mantenimiento'];
$PMF_LANG['msgMode'] = 'Modo';
$PMF_LANG['msgMaintenanceMode'] = 'FAQ en mantenimiento';
$PMF_LANG['msgOnlineMode'] = 'FAQ en línea';

// added v2.8.0-alpha3 - 2012-08-30 by Thorsten
$PMF_LANG['msgShowMore'] = 'mostrar más';
$PMF_LANG['msgQuestionAnswered'] = 'Pregunta contestada';
$PMF_LANG['msgMessageQuestionAnswered'] = 'Tu pregunta a %s fue contestada. Aquí tienes la respuesta';

// added v2.8.0-alpha3 - 2012-11-03 by Thorsten
$PMF_LANG['rightsLanguage']['addattachment'] = "Agregar adjuntos";
$PMF_LANG['rightsLanguage']['editattachment'] = "Editar adjuntos";
$PMF_LANG['rightsLanguage']['delattachment'] = "Eliminar adjuntos";
$PMF_LANG['rightsLanguage']['dlattachment'] = "Descargar adjuntos";
$PMF_LANG['rightsLanguage']['reports'] = "Crear informes";
$PMF_LANG['rightsLanguage']['addfaq'] = "Añadir FAQs en el frontend";
$PMF_LANG['rightsLanguage']['addquestion'] = "Añadir preguntas en el frontend";
$PMF_LANG['rightsLanguage']['addcomment'] = "Añadir comentarios en el frontend";
$PMF_LANG['rightsLanguage']['editinstances'] = "Editar multi-sitio";
$PMF_LANG['rightsLanguage']['addinstances'] = "Añadir multi-site";
$PMF_LANG['rightsLanguage']['delinstances'] = "Eliminar multisitios";
$PMF_LANG['rightsLanguage']['export'] = "Exportar FAQs";

// added v2.8.0-beta - 2012-12-24 by Thorsten
$LANG_CONF['records.randomSort'] = [0 => 'checkbox', 1 => 'Ordenar FAQs al azar '];
$LANG_CONF['main.enableWysiwygEditorFrontend'] = [0 => 'checkbox', 1 => 'Habilitar editor WYSIWYG incluido en el frontend '];

// added v2.8.0-beta3 - 2013-01-15 by Thorsten
$LANG_CONF['main.enableGravatarSupport'] = [0 => 'checkbox', 1 => 'Soporte para Gravatar'];

// added v2.8.0-RC - 2013-01-29 by Thorsten
$PMF_LANG['ad_stopwords_desc'] = 'Por favor, seleccione un idioma para añadir o editar nuevas palabras vacías.';
$PMF_LANG['ad_visits_per_day'] = 'Visitas por día';

// added v2.8.0-RC2 - 2013-02-17 by Thorsten
$PMF_LANG['ad_instance_add'] = 'Añadir nueva instalación multisitio de phpMyFAQ';
$PMF_LANG['ad_instance_error_notwritable'] = 'La carpeta /multisite no es escribible.';
$PMF_LANG['ad_instance_url'] = 'URL de la instancia';
$PMF_LANG['ad_instance_path'] = 'Ruta de la instancia';
$PMF_LANG['ad_instance_name'] = 'Nombre de la instancia';
$PMF_LANG['ad_instance_email'] = 'E-mail del administrador';
$PMF_LANG['ad_instance_admin'] = 'Nombre de usuario del administrador';
$PMF_LANG['ad_instance_password'] = 'Contraseña del administrador';
$PMF_LANG['ad_instance_hint'] = '¡Atención: Crear una nueva instancia de phpMyFAQ tomará unos segundos!';
$PMF_LANG['ad_instance_button'] = 'Guardar instancia';
$PMF_LANG['ad_instance_error_cannotdelete'] = 'No se puede eliminar la instancia';
$PMF_LANG['ad_instance_config'] = 'Configuración de la instancia';

// added v2.8.0-RC3 - 2013-03-03 by Thorsten
$PMF_LANG['msgAboutThisNews'] = 'Sobre este mensaje';

// added v.2.8.1 - 2013-06-23 by Thorsten
$PMF_LANG['msgAccessDenied'] = 'Acceso denegado.';

// added v.2.8.21 - 2015-02-17 by Thorsten
$PMF_LANG['msgSeeFAQinFrontend'] = 'Ver FAQ en el frontend';

// added v.2.9.0-alpha - 2013-12-26 by Thorsten
$PMF_LANG['msgRelatedTags'] = 'Agregar palabra de búsqueda';
$PMF_LANG['msgPopularTags'] = 'Palabras de búsqueda populares';
$LANG_CONF['search.enableHighlighting'] = [0 => "checkbox", 1 => "Destacar las palabras encontradas"];
$LANG_CONF['main.enableRssFeeds'] = [0 => "checkbox", 1 => "Habilitar las fuentes RSS"];
$LANG_CONF['records.allowComentariosPara Invitados'] = [0 => "checkbox", 1 => "Permitir comentarios de invitados "];
$LANG_CONF['records.allowQuestionsForGuests'] = [0 => "checkbox", 1 => "Permitir preguntas de invitados "];
$LANG_CONF['records.allowNewFaqsForGuests'] = [0 => "checkbox", 1 => "Permitir nuevas FAQs de invitados "];
$PMF_LANG['ad_searchterm_del'] = 'Eliminar todas las palabras de búsqueda almacenadas';
$PMF_LANG["ad_searchterm_del_suc"] = 'Eliminación correcta de todos los términos de búsqueda.';
$PMF_LANG["ad_searchterm_del_err"] = 'No se pudieron eliminar todos los términos de búsqueda.';
$LANG_CONF['records.hideEmptyCategories'] = [0 => "checkbox", 1 => "Ocultar categorías vacías "];
$LANG_CONF['search.searchForSolutionId'] = [0 => "checkbox", 1 => "Buscar ID de la solución "];
$LANG_CONF['socialnetworks.disableAll'] = [0 => "checkbox", 1 => "Desactivar soporte para redes sociales "];
$LANG_CONF['main.enableGzipCompression'] = [0 => "checkbox", 1 => "Activar compresión GZIP"];

// añadido v2.9.0-alpha2 - 2014-08-16 por Thorsten
$PMF_LANG['ad_tag_delete_success'] = "La etiqueta fue eliminada con éxito";
$PMF_LANG['ad_tag_delete_error'] = "La etiqueta no fue eliminada porque se produjo un error.";
$PMF_LANG['seoCenter'] = "SEO"
$LANG_CONF['seo.metaTagsHome'] = [0 => "select", 1 => "Meta Tags HTML para página principal"];
$LANG_CONF['seo.metaTagsFaqs'] = [0 => "select", 1 => "Meta Tags HTML para páginas de FAQ"];
$LANG_CONF['seo.metaTagsCategories'] = [0 => "select", 1 => "Meta Tags HTML para páginas de categorias"];
$LANG_CONF['seo.metaTagsPages'] = [0 => "select", 1 => "Meta Tags HTML para páginas estáticas"];
$LANG_CONF['seo.metaTagsAdmin'] = [0 => "select", 1 => "Meta Tags HTML para páginas de admin"];
$PMF_LANG['msgMatchingQuestions'] = "Los siguientes resultados pueden responder a tu pregunta";
$PMF_LANG['msgFinishSubmission'] = "Si ninguna de las sugerencias coincide, puedes enviar la pregunta.";
$LANG_CONF['main.enableLinkVerification'] = [0 => "checkbox", 1 => "Activar verificación automática de enlaces"];
$LANG_CONF['spam.manualActivación'] = [0 => 'checkbox', 1 => 'Activar usuarios manualmente'];

// añadido v2.9.0-alpha2 - 2014-10-13 por Christopher Andrews ( Chris--A )
$PMF_LANG['mailControlCenter'] = 'E-mail';
$LANG_CONF['mail.remoteSMTP'] = [0 => 'checkbox', 1 => 'Usar servidor SMTP externo'];
$LANG_CONF['mail.remoteSMTPServer'] = [0 => 'input', 1 => 'Servidor SMTP'];
$LANG_CONF['mail.remoteSMTPNombre de usuario'] = [0 => 'input', 1 => 'Nombre de usuario SMTP'];
$LANG_CONF['mail.remoteSMTPContraseña'] = [0 => 'password', 1 => 'Contraseña SMTP'];
$LANG_CONF['security.enableRegistration'] = array('checkbox', 'Permitir el registro de visitantes');

// Añadido v2.9.0-alpha3 - 2015-02-08 por Thorsten
$LANG_CONF['main.customPdfHeader'] = ['area', 'Encabezado personalizado del PDF (HTML permitido)'];
$LANG_CONF['main.customPdfFooter']] = ['area', 'Pie de página personalizado del PDF (HTML permitido)'];
$LANG_CONF['records.allowDownloadsForGuests'] = ['checkbox', 'Permitir descargas a los invitados'];
$PMF_LANG['ad_msgNoteAboutPasswords'] = "¡Atención! Al rellenar los campos de la contraseña, sobreescribes la contraseña del usuario";
$PMF_LANG['ad_delete_all_votings'] = "Eliminar todos los votos";
$PMF_LANG['ad_categ_moderator'] = "Moderadores";
$PMF_LANG['ad_clear_all_visits'] = "Reiniciar todas las visitas";
$PMF_LANG['ad_reset_visits_success'] = 'Las visitas fueron reiniciadas con éxito';
$LANG_CONF['main.enableMarkdownEditor']]] = ['checkbox', 'Activar el editor Markdown'];

//// añadido v2.9.0-beta - 2015-09-27 por Thorsten
$PMF_LANG['faqOverview'] = 'Resumen de FAQ';
$PMF_LANG['ad_dir_missing'] = 'Falta la carpeta %s';
$LANG_CONF['main.enableSmartAnswering'] = ['checkbox', 'Habilitar la respuesta inteligente en las preguntas de usuario'];

// añadido v2.9.0-beta2 - 2015-12-23 por Thorsten
$LANG_CONF['search.enableElasticsearch'] = ['checkbox', 'Activar soporte para Elasticsearch'];
$PMF_LANG['ad_menu_elasticsearch'] = 'Configuración Elasticsearch';
$PMF_LANG['ad_es_create_index'] = 'Crear índice de búsqueda';
$PMF_LANG['ad_es_drop_index'] = 'Eliminar índice de búsqueda';
$PMF_LANG['ad_es_bulk_index'] = 'Importación completa';
$PMF_LANG['ad_es_create_index_success'] = 'El índice de búsqueda de Elasticsearch fue creado con éxito.';
$PMF_LANG['ad_es_drop_index_success'] = 'El índice de búsqueda de Elasticsearch ha sido eliminado con éxito.';
$PMF_LANG['ad_export_generate_json'] = 'Exportar como archivo JSON';
$PMF_LANG['ad_image_name_search'] = 'Buscar nombres de imágenes';

// añadido v2.9.0-RC - 2016-02-19 por Thorsten
$PMF_LANG['ad_admin_notes'] = 'Notas privadas';
$PMF_LANG['ad_admin_notes_hint'] = '%s (sólo visible para los editores)';

// Añadido v2.9.10 - 2018-02-17 por Thorsten
$PMF_LANG['ad_quick_entry'] = 'Crear nueva FAQ en esta categoría';

// añadido 2.10.0-alfa - 2016-08-08 por Thorsten
$LANG_CONF['ldap.ldap_mapping.name'] = [0 => 'input', 1 => 'Mapeo LDAP para Nombre, "cn" cuando se usa un ADS'];
$LANG_CONF['ldap.ldap_mapping.username'] = [0 => 'input', 1 => 'Mapeo LDAP para Nombre de Usuario, "samAccountName" cuando se usa un ADS'];
$LANG_CONF['ldap.ldap_mapping.mail'] = [0 => 'input', 1 => 'Mapeo LDAP para e-mail, "mail" cuando se usa un ADS'];
$LANG_CONF['ldap.ldap_mapping.memberOf'] = [0 => 'input', 1 => 'mapeo LDAP para "Miembro de" cuando se usan grupos LDAP'];
$LANG_CONF['ldap.ldap_use_domain_prefix'] = ['checkbox', 'Prefijo de dominio LDAP, p.ej. "DOMAIN\username"'];
$LANG_CONF['ldap.ldap_options.LDAP_OPT_PROTOCOL_VERSION'] = [0 => 'input', 1 => 'versión del protocolo LDAP (por defecto: 3)'];
$LANG_CONF['ldap.ldap_options.LDAP_OPT_REFERRALS'] = [0 => 'input', 1 => 'Referencias LDAP (por defecto: 0)'];
$LANG_CONF['ldap.ldap_use_memberOf'] = ['checkbox', 'Soporte para grupos LDAP, p.ej. "DOMAIN\nombredeusuario"'];
$LANG_CONF['ldap.ldap_use_sasl'] = ['checkbox', 'Soporte para LDAP con SASL'];
$LANG_CONF['ldap.ldap_use_multiple_servers'] = ['checkbox', 'Soporte para múltiples servidores LDAP'];
$LANG_CONF['ldap.ldap_use_anonymous_login'] = ['checkbox', 'Soporte para conexiones LDAP anónimas'];
$LANG_CONF['ldap.ldap_use_dynamic_login'] = ['checkbox', 'Soporte para la vinculación dinámica del usuario'];
$LANG_CONF['ldap.ldap_dynamic_login_attribute'] = [0 => 'input', 1 => 'Atributo LDAP para la vinculación dinámica del usuario, "uid" cuando se usa un ADS'];
$LANG_CONF['seo.enableXMLSitemap'] = ['checkbox', 'Habilitar el mapa del sitio XML'];
$PMF_LANG['ad_category_image'] = 'Categoría de la imagen';
$PMF_LANG['ad_user_show_home'] = "Mostrar en la página de inicio";

// añadido v.2.10.0-alfa - 2017-11-09 por Brian Potter (BrianPotter)
$PMF_LANG['ad_view_faq'] = 'Ver FAQ';

// añadido 3.0.0-alfa - 2018-01-04 por Thorsten
$LANG_CONF['main.enableCategoryRestrictions'] = ['checkbox', 'Habilitar las restricciones de categoría'];
$LANG_CONF['main.enableSendToFriend'] = ['checkbox', 'Habilitar recomendación'];
$PMF_LANG['msgUserRemovalText']] = 'Puedes solicitar la eliminación de tu cuenta y tus datos personales. Se enviará un e-mail al equipo de administración. El equipo eliminará su cuenta, comentarios y preguntas. Como es un proceso manual, puede tardar hasta 24 horas. Después de eso, recibirás una confirmación de eliminación por e-mail.;';
$PMF_LANG['msgUserRemoval'] = "Solicitud de eliminación del usuario";
$PMF_LANG['ad_menu_RequestRemove'] = "Eliminar usuario";
$PMF_LANG['msgContactRemove'] = "Solicitud de eliminación del usuario del equipo de administración";
$PMF_LANG['msgContactPrivacyNote'] = "Por favor, tome nota de nuestra";
$PMF_LANG['msgPrivacyNote'] = "Política de Privacidad";

// añadido 3.0.0-alfa2 - 2018-03-27 por Thorsten
$LANG_CONF['main.enableAutoUpdateHint'] = ['checkbox', 'Comprobación automática de nuevas versiones'];
$PMF_LANG['ad_user_is_superadmin'] = 'Super-Admin';
$PMF_LANG['ad_user_override_passwd'] = 'Anular contraseña';
$LANG_CONF['records.enableAutoRevisions'] = ['checkbox', 'Versiones para cambio en la FAQ'];
$PMF_LANG['rightsLanguage']['view_faqs'] = 'Ver FAQs';
$PMF_LANG['rightsLanguage']['view_categories'] = 'Ver categorías';
$PMF_LANG['rightsLanguage']['view_sections'] = 'Ver secciones';
$PMF_LANG['rightsLanguage']['view_news'] = 'Ver noticias';
$PMF_LANG['rightsLanguage']['add_section'] = 'Añadir sección';
$PMF_LANG['rightsLanguage']['edit_section'] = 'Editar sección';
$PMF_LANG['rightsLanguage']['delete_section'] = 'Eliminar sección';
$PMF_LANG['rightsLanguage']['administrate_sections'] = 'Administrar secciones';
$PMF_LANG['rightsLanguage']['administrate_groups'] = 'Administrar grupos';
$PMF_LANG['ad_group_rights'] = 'Permisos de grupo';
$PMF_LANG['ad_menu_meta'] = 'Plantilla de metadatos';
$PMF_LANG['ad_meta_add'] = 'Añadir plantilla de metadatos';
$PMF_LANG['ad_meta_page_id'] = 'Tipo de página';
$PMF_LANG['ad_meta_type'] = 'Tipo de contenido';
$PMF_LANG['ad_meta_content'] = 'Contenido';
$PMF_LANG['ad_meta_copy_snippet'] = 'Copiar fragmento de código para plantillas';

// añadido v3.0.0-alpha.3 - 2018-09-20 por Timo
$PMF_LANG['ad_menu_section_administration'] = "Secciones";
$PMF_LANG['ad_section_add'] = "Añadir sección";
$PMF_LANG['ad_section_add_link'] = "Añadir sección";
$PMF_LANG['ad_sections'] = 'Secciones';
$PMF_LANG['ad_section_details'] = "Detalles de la sección";
$PMF_LANG['ad_section_name'] = "Nombre";
$PMF_LANG['ad_section_description'] = "Descripción";
$PMF_LANG['ad_section_membership'] = "Asignación de sección";
$PMF_LANG['ad_section_members'] = "Asignaciones";
$PMF_LANG['ad_section_addMember'] = "+";
$PMF_LANG['ad_section_removeMember'] = "-";
$PMF_LANG['ad_section_deleteSection'] = "Eliminar sección";
$PMF_LANG['ad_section_deleteQuestion'] = "¿Estás seguro de que quieres eliminar esta sección?";
$PMF_LANG['ad_section_error_delete'] = "La sección no pudo ser eliminada.";
$PMF_LANG['ad_section_error_noName'] = "Por favor, introduzca un nombre para la sección.";
$PMF_LANG['ad_section_suc'] = "La sección ha sido añadida <strong>correctamente</strong>.";
$PMF_LANG['ad_section_deleted'] = "La sección fue eliminada con éxito.";
$PMF_LANG['rightsLanguage']['viewadminlink'] = 'Ver enlace a la administración';

// añadido v3.0.0-beta.3 - 2019-09-22 por Thorsten
$LANG_CONF['mail.remoteSMTPPort'] = [0 => 'input', 1 => 'Puerto del servidor SMTP'];
$LANG_CONF['mail.remoteSMTPEncryption'] = [0 => 'input', 1 => 'Encriptación del servidor SMTP'];
$PMF_LANG['ad_record_faq'] = 'Pregunta y Respuesta';
$PMF_LANG['ad_record_permissions'] = 'Permisos';
$PMF_LANG['loginPageMessage'] = 'Login para ';

// añadido v3.1.0-alfa - 2020-03-27 por Thorsten
$PMF_LANG['ad_user_data_is_visible'] = 'El nombre de usuario debe ser visible';