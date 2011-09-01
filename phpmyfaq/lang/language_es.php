<? Php
/ **
 * El archivo de idioma Inglés - tratar de ser el mejor de los británicos y estadounidenses Inglés
 *
 * PHP versión 5.2
 *
 * El contenido de este archivo está sujeto a la Licencia Pública de Mozilla
 * Versión 1.1 (la "Licencia"), usted no puede utilizar este archivo, excepto en
 * El cumplimiento de la licencia. Usted puede obtener una copia de la Licencia en
 * Http://www.mozilla.org/MPL/
 *
 * El software se distribuye bajo la licencia se distribuye "TAL CUAL"
 Base *, SIN GARANTÍA DE NINGÚN TIPO, ya sea expresa o implícita. Ver el
 * Licencia para el idioma específico que rige los derechos y limitaciones
 * Bajo la licencia.
 *
 * @ Categoría phpMyFAQ
 * @ Package Traducción
 * @ Author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @ Autor Matthias Sommerfeld <mso@bluebirdy.de>
 * @ Autor Henning Schulzrinne <hgs@cs.columbia.edu>
 * @ Copyright 2004-2010 phpMyFAQ equipo
 * @ Licencia http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License versión 1.1
 * @ Link http://www.phpmyfaq.de
 * @ Since 02/19/2004
 * /

/ **
 *! NOTA IMPORTANTE!
 * Por favor, considere lo siguiente al definir vars nuevo:
 * - Una definición de la variable por línea!
 * - El caso ideal es definir un valor de cadena escalar
 * - Si algún contenido dinámico es necesario, utilice la sintaxis sprintf
 * - Los arreglos son permitidos, pero no se recomienda
 * - No hay comentarios al final de la línea después de la definición var
 * - No utilizar '=' char en las claves del array
 * (Eq. $ PMF_LANG ["a = b"] no está permitido)
 *
 * Por favor, ser compatibles con este formato tal y como la necesidad de
 * La herramienta de traducción para trabajar propertly
 * /

$ PMF_LANG ["metaCharset"] = "UTF-8";
$ PMF_LANG ["metalenguaje"] = "en";
$ PMF_LANG ["idioma"] = "Inglés";
/ / L: de izquierda a derecha (por ejemplo, el idioma Inglés), rtl: de derecha a izquierda (por ejemplo, en árabe)
$ PMF_LANG ["dir"] = "ltr";

$ PMF_LANG ["nplurals"] = "2";
/ **
 * Este parámetro se utiliza con "plural" la función del inc / PMF_Language / Plurals.php
 * Si este parámetro y la función no son en apoyo a la forma plural de sincronización se habrá roto.
 *
 * Si se agrega una traducción de un idioma nuevo, el apoyo correcta forma plural no podrá contar con
 * (Mensajes de Inglés plural se usa) hasta que se agrega una expresión correcta de la función
 * "Plural" mencionado anteriormente.
 * Si usted necesita cualquier ayuda, por favor póngase en contacto con el equipo phpMyFAQ.
 * /

/ / Navegación
$ PMF_LANG ["msgCategory"] = "Categorías";
$ PMF_LANG ["msgShowAllCategories"] = "Todas las familias";
$ PMF_LANG ["msgSearch"] = "Buscar";
$ PMF_LANG ["msgAddContent"] = "Añadir FAQ";
$ PMF_LANG ["msgQuestion"] = "Añadir pregunta";
$ PMF_LANG ["msgOpenQuestions"] = "preguntas abiertas";
$ PMF_LANG ["msgHelp"] = "Ayuda";
$ PMF_LANG ["msgContact"] = "Contacto";
$ PMF_LANG ["msgHome"] = "FAQ Home";
$ PMF_LANG ["msgNews"] = "FAQ Noticias";
$ PMF_LANG ["msgUserOnline"] = "Usuarios en línea";
$ PMF_LANG ["msgBack2Home"] = "Volver a la página principal";

/ / Contentpages
$ PMF_LANG ["msgFullCategories"] = "Categorías con preguntas frecuentes";
$ PMF_LANG ["msgFullCategoriesIn"] = "Categorías con preguntas frecuentes de";
$ PMF_LANG ["msgSubCategories"] = "Subcategorías";
$ PMF_LANG ["msgEntries"] = "Preguntas frecuentes";
$ PMF_LANG ["msgEntriesIn"] = "Las preguntas de";
$ PMF_LANG ["msgViews"] = "vistas";
$ PMF_LANG ["msgPage"] = "Page";
$ PMF_LANG ["msgPages"] = "Páginas";
$ PMF_LANG ["msgPrevious"] = "anterior";
$ PMF_LANG ["msgNext"] = "siguiente";
$ PMF_LANG ["msgCategoryUp"] = "una de las categorías arriba";
$ PMF_LANG ["msgLastUpdateArticle"] = "Última actualización:";
$ PMF_LANG ["msgAuthor"] = "Autor:";
$ PMF_LANG ["msgPrinterFriendly"] = "versión para imprimir";
$ PMF_LANG ["msgPrintArticle"] = "Imprimir este registro";
$ PMF_LANG ["msgMakeXMLExport"] = "Exportar como XML-archivo";
$ PMF_LANG ["msgAverageVote"] = "Nota media:";
$ PMF_LANG ["msgVoteUseability"] = "Por favor califique esta FAQ:";
$ PMF_LANG ["msgVoteFrom"] = "de";
$ PMF_LANG ["msgVoteBad"] = "completamente inútil";
$ PMF_LANG ["msgVoteGood"] = "más valiosos";
$ PMF_LANG ["msgVotings"] = "Votos";
$ PMF_LANG ["msgVoteSubmit"] = "voto";
$ PMF_LANG ["msgVoteThanks"] = "Muchas gracias por tu voto!";
$ PMF_LANG ["msgYouCan"] = "Puede";
$ PMF_LANG ["msgWriteComment"] = "añadir un comentario";
$ PMF_LANG ["msgShowCategory"] = "Visión general del contenido:";
$ PMF_LANG ["msgCommentBy"] = "Comentario de";
$ PMF_LANG ["msgCommentHeader"] = "añadir un comentario";
$ PMF_LANG ["msgYourComment"] = "Tu comentario:";
$ PMF_LANG ["msgCommentThanks"] = "Muchas gracias por tu comentario!";
$ PMF_LANG ["msgSeeXMLFile"] = "Open XML File";
$ PMF_LANG ["msgSend2Friend"] = "Enviar a un amigo Preguntas frecuentes";
$ PMF_LANG ["msgS2FName"] = "Su nombre:";
$ PMF_LANG ["msgS2FEMail"] = "Su dirección de correo electrónico:";
$ PMF_LANG ["msgS2FFriends"] = "Tus amigos:";
$ PMF_LANG ["msgS2FEMails"] = "dirección de correo electrónico:.";
$ PMF_LANG ["msgS2FText"] = "El siguiente texto se enviará:";
$ PMF_LANG ["msgS2FText2"] = "Usted encontrará las preguntas más frecuentes en la siguiente dirección:";
$ PMF_LANG ["msgS2FMessage"] = "Mensaje adicional para tus amigos:";
$ PMF_LANG ["msgS2FButton"] = "Enviar e-mail";
$ PMF_LANG ["msgS2FThx"] = "Gracias por tu recomendación!";
$ PMF_LANG ["msgS2FMailSubject"] = "Recomendación de";

/ / Buscar
$ PMF_LANG ["msgSearchWord"] = "palabras clave";
$ PMF_LANG ["msgSearchFind"] = "Resultado de la búsqueda para";
$ PMF_LANG ["msgSearchAmount"] = "resultado de la búsqueda";
$ PMF_LANG ["msgSearchAmounts"] = "los resultados de búsqueda";
$ PMF_LANG ["msgSearchCategory"] = "Categoría";
$ PMF_LANG ["msgSearchContent"] = "Respuesta:";

/ / Nuevos Contenidos
$ PMF_LANG ["msgNewContentHeader"] = "Propuesta de un nuevo FAQ";
$ PMF_LANG ["msgNewContentAddon"] = "Su propuesta no se publicará de inmediato, pero se dará a conocer por el moderador. Los campos obligatorios están <strong> su nombre </ strong>, <strong> su dirección de correo electrónico </ strong .>, <strong> categoría </ strong>, <strong> título </ strong> y <strong> su registro </ strong> Por favor separa las palabras clave con un espacio único ".;
$ PMF_LANG ["msgNewContentName"] = "Su nombre:";
$ PMF_LANG ["msgNewContentMail"] = "Su dirección de correo electrónico:";
$ PMF_LANG ["msgNewContentCategory"] = "Categoría";
$ PMF_LANG ["msgNewContentTheme"] = "Tu pregunta:";
$ PMF_LANG ["msgNewContentArticle"] = "Su respuesta:";
$ PMF_LANG ["msgNewContentKeywords"] = "Palabras claves:";
$ PMF_LANG ["msgNewContentLink"] = "Enlace de esta FAQ:";
$ PMF_LANG ["msgNewContentSubmit"] = "Enviar";
$ PMF_LANG ["msgInfo"] = "Más información";
$ PMF_LANG ["msgNewContentThanks"] = "Gracias por tu sugerencia!";
$ PMF_LANG ["msgNoQuestionsAvailable"] = "Actualmente no hay cuestiones pendientes.";

/ / Preguntar Pregunta
$ PMF_LANG ["msgNewQuestion"] = "Haz tu pregunta a continuación:";
$ PMF_LANG ["msgAskCategory"] = "Categoría";
$ PMF_LANG ["msgAskYourQuestion"] = "Tu pregunta:";
$ PMF_LANG ["msgAskThx4Mail"] = "<h2> Gracias por su pregunta </ ??h2>";
$ PMF_LANG ["msgDate_User"] = "Fecha / usuario";
$ PMF_LANG ["msgQuestion2"] = "Pregunta";
$ PMF_LANG ["msg2answer"] = "Respuesta";
$ PMF_LANG ["msgQuestionText"] = "Aquí puedes ver las preguntas formuladas por otros usuarios Si la respuesta a estas preguntas, sus respuestas pueden ser insertados en el FAQ..";

/ / Ayuda
$ PMF_LANG ["msgHelpText"] = "<p> La estructura de las preguntas más frecuentes (<strong> F </ strong> requently <strong> A </ strong> Sked <strong> Q </ strong> reguntas) es bastante simple. Usted puede buscar en el <strong> href=\"?action=show\"> categorías </ a> </ strong> o dejar que el <strong> <a href=\"?action=search\"> FAQ los motores de búsqueda </ a> </ strong> búsqueda por palabras clave </ p> ".;

/ / Contacto
$ PMF_LANG ["msgContactEMail"] = "El correo electrónico al webmaster:";
$ PMF_LANG ["msgMessage"] = "Tu mensaje:";

/ / Startseite
$ PMF_LANG ["msgNews"] = "Noticias";
$ PMF_LANG ["msgTopTen"] = "Preguntas frecuentes más populares";
$ PMF_LANG ["msgHomeThereAre"] = "No";
$ PMF_LANG ["msgHomeArticlesOnline"] = "preguntas frecuentes en línea";
$ PMF_LANG ["msgNoNews"] = "No hay noticias son buenas noticias.";
$ PMF_LANG ["msgLatestArticles"] = "preguntas más frecuentes";

/ / E-Mailbenachrichtigung
$ PMF_LANG ["msgMailThanks"] = "Muchas gracias por su propuesta a las preguntas más frecuentes.";
$ PMF_LANG ["msgMailCheck"] = "Hay una nueva entrada en la FAQ Por favor, consulte la sección de administración!";
$ PMF_LANG ["msgMailContact"] = "Su mensaje ha sido enviado al administrador.";

/ / Fehlermeldungen
$ PMF_LANG ["err_noDatabase"] = "No hay conexión de bases de datos disponibles.";
$ PMF_LANG ["err_noHeaders"] = "No se encuentra la categoría.";
$ PMF_LANG ["err_noArticles"] = "No <p> Preguntas frecuentes disponibles </ p>.";
$ PMF_LANG ["err_badID"] = "ID <p> mal </ p>.";
$ PMF_LANG ["err_noTopTen"] = "No <p> Preguntas frecuentes populares disponibles todavía </ p>.";
$ PMF_LANG ["err_nothingFound"] = "<p> hay artículos </ p>.";
$ PMF_LANG ["err_SaveEntries"] = "Los campos obligatorios están <strong> su nombre </ strong>, <strong> su dirección de correo electrónico </ strong>, <strong> categoría </ strong>, <strong> título </ strong> , <strong> su Registro </ strong> y, cuando se le solicite, la Wikipedia <strong> <a href = \ "http://en.wikipedia.org/wiki/Captcha \" title = \ "Leer más sobre Captcha en \ "target = \" _blank \ "> Captcha </ a> code> </ strong <br /> <br /> <a href=\"javascript:history.back();\"> una última página < / a> <br /> <br /> ";
$ PMF_LANG ["err_SaveComment"] = "Los campos obligatorios están <strong> su nombre </ strong>, <strong> su dirección de correo electrónico </ strong>, <strong> sus comentarios </ strong> y, cuando lo solicite, el <fuerte > <a href=\"http://en.wikipedia.org/wiki/Captcha\" title=\"Read más sobre Captcha Captcha en Wikipedia\" target=\"_blank\"> </ a> código </ strong> <br /> <br /> <a href=\"javascript:history.back();\"> una última página </ a> <br /> <br /> "!;
$ PMF_LANG ["err_VoteTooMuch"] = "<p> No contamos votaciones dobles. Href=\"javascript:history.back();\"> <a Haga clic aquí </ a>, para volver. </ P > ";
$ PMF_LANG ["err_noVote"] = "<p> Usted no tasa a la pregunta: </ strong> <a href=\"javascript:history.back();\"> Por favor, haga clic aquí </ a> , para votar </ p> ".;
$ PMF_LANG ["err_noMailAdress"] = "Su dirección de correo electrónico no es correcto <br /> href=\"javascript:history.back();\"> atrás </ a>.";
$ PMF_LANG ["err_sendMail"] = "Los campos obligatorios están <strong> su nombre </ strong>, <strong> su dirección de correo electrónico </ strong>, <strong> su pregunta </ ??strong> y, cuando lo solicite, el <fuerte > <a href=\"http://en.wikipedia.org/wiki/Captcha\" title=\"Read más sobre Captcha Captcha en Wikipedia\" target=\"_blank\"> </ a> código </ strong> <br /> <br /> <a href=\"javascript:history.back();\"> una última página </ a> <br /> <br /> "!;

/ / Hilfe zur Suche
$ PMF_LANG ["help_search"] = "Buscar <p> de registros: </ strong> <br /> Con una entrada como <strong style=\"color: Red;\"> palabra1 palabra2 </ strong> que usted puede hacer una búsqueda Relevancia para el criterio de búsqueda de dos o más </ p> <strong> Aviso:.. </ strong> Su criterio de búsqueda tiene que ser por lo menos cuatro cartas largas de lo contrario su solicitud será rechazada </ p> ";

/ / Meni ¿½
$ PMF_LANG ["ad"] = "sección de administración";
$ PMF_LANG ["ad_menu_user_administration"] = "Usuarios";
$ PMF_LANG ["ad_menu_entry_aprove"] = "Aprobar Preguntas frecuentes";
$ PMF_LANG ["ad_menu_entry_edit"] = "Editar Preguntas frecuentes";
$ PMF_LANG ["ad_menu_categ_add"] = "Añadir categoría";
$ PMF_LANG ["ad_menu_categ_edit"] = "Editar categoría";
$ PMF_LANG ["ad_menu_news_add"] = "Añadir noticias";
$ PMF_LANG ["ad_menu_news_edit"] = "Editar noticias";
$ PMF_LANG ["ad_menu_open"] = "preguntas abiertas";
$ PMF_LANG ["ad_menu_stat"] = "Estadísticas";
$ PMF_LANG ["ad_menu_cookie"] = "Fijar cookies";
$ PMF_LANG ["ad_menu_session"] = "Ver sesiones";
$ PMF_LANG ["ad_menu_adminlog"] = "Ver Adminlog";
$ PMF_LANG ["ad_menu_passwd"] = "Cambiar contraseña";
$ PMF_LANG ["ad_menu_logout"] = "Cerrar sesión";
$ PMF_LANG ["ad_menu_startpage"] = "Página de inicio";

/ / Nachrichten
$ PMF_LANG ["ad_msg_identify"] = "Por favor, identifíquese.";
$ PMF_LANG ["ad_msg_passmatch"] = "Ambas contraseñas <strong> deben coincidir </ strong>";
$ PMF_LANG ["ad_msg_savedsuc_1"] = "El perfil del";
$ PMF_LANG ["ad_msg_savedsuc_2"] = "se ha guardado correctamente.";
$ PMF_LANG ["ad_msg_mysqlerr"] = "Debido a un error de base de datos <strong> </ strong>, el perfil no puede ser salvo.";
$ PMF_LANG ["ad_msg_noauth"] = "Usted no está autorizado.";

/ / Allgemein
$ PMF_LANG ["ad_gen_page"] = "Page";
$ PMF_LANG ["ad_gen_of"] = "de";
$ PMF_LANG ["ad_gen_lastpage"] = "Página anterior";
$ PMF_LANG ["ad_gen_nextpage"] = "Página siguiente";
$ PMF_LANG ["ad_gen_save"] = "Guardar";
$ PMF_LANG ["ad_gen_reset"] = "Reset";
$ PMF_LANG ["ad_gen_yes"] = "Sí";
$ PMF_LANG ["ad_gen_no"] = "No";
$ PMF_LANG ["ad_gen_top"] = "Arriba";
$ PMF_LANG ["ad_gen_ncf"] = "No se encuentra la categoría";
$ PMF_LANG ["ad_gen_delete"] = "Eliminar";

/ / Benutzerverwaltung
$ PMF_LANG ["ad_user"] = "Administración de usuarios";
$ PMF_LANG ["ad_user_username"] = "Los usuarios registrados";
$ PMF_LANG ["ad_user_rights"] = "Los derechos de usuario";
$ PMF_LANG ["ad_user_edit"] = "Editar";
$ PMF_LANG ["ad_user_delete"] = "eliminar";
$ PMF_LANG ["ad_user_add"] = "Añadir usuario";
$ PMF_LANG ["ad_user_profou"] = "El perfil del usuario";
$ PMF_LANG ["ad_user_name"] = "Nombre";
$ PMF_LANG ["ad_user_password"] = "Contraseña";
$ PMF_LANG ["ad_user_confirm"] = "Confirmar";
$ PMF_LANG ["ad_user_rights"] = "Derechos";
$ PMF_LANG ["ad_user_del_1"] = "El Usuario";
$ PMF_LANG ["ad_user_del_2"] = "Se suprime?";
$ PMF_LANG ["ad_user_del_3"] = "¿Está seguro?";
$ PMF_LANG ["ad_user_deleted"] = "El usuario se ha eliminado correctamente.";
$ PMF_LANG ["ad_user_checkall"] = "Seleccionar todo";

/ / Beitragsverwaltung
$ PMF_LANG ["ad_entry_aor"] = "FAQ administración";
$ PMF_LANG ["ad_entry_id"] = "ID";
$ PMF_LANG ["ad_entry_topic"] = "Mensaje";
$ PMF_LANG ["ad_entry_action"] = "Acción";
$ PMF_LANG ["ad_entry_edit_1"] = "Editar registro";
$ PMF_LANG ["ad_entry_edit_2"] = "";
$ PMF_LANG ["ad_entry_theme"] = "Pregunta:";
$ PMF_LANG ["ad_entry_content"] = "Respuesta:";
$ PMF_LANG ["ad_entry_keywords"] = "Palabras claves:";
$ PMF_LANG ["ad_entry_author"] = "Autor:";
$ PMF_LANG ["ad_entry_category"] = "Categoría";
$ PMF_LANG ["ad_entry_active"] = "visible";
$ PMF_LANG ["ad_entry_date"] = "Fecha:";
$ PMF_LANG ["ad_entry_changed"] = "ha cambiado?";
$ PMF_LANG ["ad_entry_changelog"] = "cambios";
$ PMF_LANG ["ad_entry_commentby"] = "Comentario por";
$ PMF_LANG ["ad_entry_comment"] = "Comentarios:";
$ PMF_LANG ["ad_entry_save"] = "Guardar";
$ PMF_LANG ["ad_entry_delete"] = "eliminar";
$ PMF_LANG ["ad_entry_delcom_1"] = "¿Estás seguro de que el comentario del usuario";
$ PMF_LANG ["ad_entry_delcom_2"] = "debe ser eliminado?";
$ PMF_LANG ["ad_entry_commentdelsuc"] = "El comentario fue <strong> éxito </ strong> eliminado.";
$ PMF_LANG ["ad_entry_back"] = "Volver al artículo";
$ PMF_LANG ["ad_entry_commentdelfail"] = "El comentario <strong> no </ strong> eliminado.";
$ PMF_LANG ["ad_entry_savedsuc"] = "Los cambios se han guardado con éxito <strong> </ strong>.";
$ PMF_LANG ["ad_entry_savedfail"] = "Por desgracia, un error de base de datos <strong> </ strong> se produjo.";
$ PMF_LANG ["ad_entry_del_1"] = "¿Estás seguro de que el tema";
$ PMF_LANG ["ad_entry_del_2"] = "de";
$ PMF_LANG ["ad_entry_del_3"] = "debe ser eliminado?";
$ PMF_LANG ["ad_entry_delsuc"] = "Problema <strong> éxito </ strong> eliminado.";
$ PMF_LANG ["ad_entry_delfail"] = "Problema no <strong> borrado </ strong>";
$ PMF_LANG ["ad_entry_back"] = "Volver";


/ / Newsverwaltung
$ PMF_LANG ["ad_news_header"] = "encabezado del artículo:";
$ PMF_LANG ["ad_news_text"] = "Texto de la grabación:";
$ PMF_LANG ["ad_news_link_url"] = "link:";
$ PMF_LANG ["ad_news_link_title"] = "Título del enlace:";
$ PMF_LANG ["ad_news_link_target"] = "Objetivo de la conexión:";
$ PMF_LANG ["ad_news_link_window"] = "El enlace se abre una nueva ventana";
$ PMF_LANG ["ad_news_link_faq"] = "Enlace a las preguntas más frecuentes";
$ PMF_LANG ["ad_news_add"] = "Añadir News";
$ PMF_LANG ["ad_news_id"] = "#";
$ PMF_LANG ["ad_news_headline"] = "Título";
$ PMF_LANG ["ad_news_date"] = "Fecha";
$ PMF_LANG ["ad_news_action"] = "Acción";
$ PMF_LANG ["ad_news_update"] = "actualización";
$ PMF_LANG ["ad_news_delete"] = "eliminar";
$ PMF_LANG ["ad_news_nodata"] = "No se encontraron datos en la base de datos";
$ PMF_LANG ["ad_news_updatesuc"] = "Las noticias fueron actualizadas.";
$ PMF_LANG ["ad_news_del"] = "¿Está seguro que desea eliminar esta noticia?";
$ PMF_LANG ["ad_news_yesdelete"] = "yes, borrar!";
$ PMF_LANG ["ad_news_nodelete"] = "no";
$ PMF_LANG ["ad_news_delsuc"] = "Noticia eliminado.";
$ PMF_LANG ["ad_news_updatenews"] = "Noticia actualizada.";

/ / Kategorieverwaltung
$ PMF_LANG ["ad_categ_new"] = "Agregar una nueva categoría";
$ PMF_LANG ["ad_categ_catnum"] = "Número de categoría:";
$ PMF_LANG ["ad_categ_subcatnum"] = "número Subcategoría:";
$ PMF_LANG ["ad_categ_nya"] = "<em> aún no está disponible </ em>";
$ PMF_LANG ["ad_categ_titel"] = "Título de la categoría:";
$ PMF_LANG ["ad_categ_add"] = "Añadir categoría";
$ PMF_LANG ["ad_categ_existing"] = "categorías existentes";
$ PMF_LANG ["ad_categ_id"] = "#";
$ PMF_LANG ["ad_categ_categ"] = "Categoría";
$ PMF_LANG ["ad_categ_subcateg"] = "subcategoría";
$ PMF_LANG ["ad_categ_titel"] = "Título de la categoría";
$ PMF_LANG ["ad_categ_action"] = "Acción";
$ PMF_LANG ["ad_categ_update"] = "actualización";
$ PMF_LANG ["ad_categ_delete"] = "eliminar";
$ PMF_LANG ["ad_categ_updatecateg"] = "Categoría de actualización";
$ PMF_LANG ["ad_categ_nodata"] = "No se encontraron datos en la base de datos";
$ PMF_LANG ["ad_categ_remark"] = "Por favor, tenga en cuenta que las entradas existentes no se verán nunca más, si se elimina la categoría, debe asignar una nueva categoría para el artículo o eliminar el artículo..";
$ PMF_LANG ["ad_categ_edit_1"] = "Editar";
$ PMF_LANG ["ad_categ_edit_2"] = "Categoría";
$ PMF_LANG ["ad_categ_add"] = "Añadir Categoría";
$ PMF_LANG ["ad_categ_added"] = "La categoría fue añadida.";
$ PMF_LANG ["ad_categ_updated"] = "La categoría fue actualizada.";
$ PMF_LANG ["ad_categ_del_yes"] = "yes, borrar!";
$ PMF_LANG ["ad_categ_del_no"] = "no";
$ PMF_LANG ["ad_categ_deletesure"] = "¿Está seguro de eliminar esta categoría?";
$ PMF_LANG ["ad_categ_deleted"] = "Categoría eliminado.";

/ / Cookies
$ PMF_LANG ["ad_cookie_cookiesuc"] = "El cookie se <strong> éxito </ strong> set.";
$ PMF_LANG ["ad_cookie_already"] = "Una cookie se estableció ya Ahora tiene las opciones siguientes:.";
$ PMF_LANG ["ad_cookie_again"] = "cookie Set de nuevo";
$ PMF_LANG ["ad_cookie_delete"] = "Eliminar cookies";
$ PMF_LANG ["ad_cookie_no"] = "No hay ninguna cookie guardada con todo con una cookie que usted podría ahorrar el script de conexión, por lo tanto no hay necesidad de recordar sus datos de acceso otra vez Ahora tiene las siguientes opciones:..";
$ PMF_LANG ["ad_cookie_set"] = "cookie Set";
$ PMF_LANG ["ad_cookie_deleted"] = "Cookie eliminado con éxito.";

/ / Adminlog
$ PMF_LANG ["ad_adminlog"] = "AdminLog";

/ / Passwd
$ PMF_LANG ["ad_passwd_cop"] = "Cambiar contraseña";
$ PMF_LANG ["ad_passwd_old"] = "Contraseña anterior";
$ PMF_LANG ["ad_passwd_new"] = "Nueva contraseña:";
$ PMF_LANG ["ad_passwd_con"] = "Confirmar:";
$ PMF_LANG ["ad_passwd_change"] = "Cambiar contraseña";
$ PMF_LANG ["ad_passwd_suc"] = "Contraseña cambiada con éxito.";
$ PMF_LANG ["ad_passwd_remark"] = "<strong> ATENCIÓN: </ strong> <br /> cookies tienen que ser ajustado de nuevo";
$ PMF_LANG ["ad_passwd_fail"] = "La contraseña antigua <strong> debe </ strong> se ha introducido correctamente y tanto los nuevos tienen que coincidir con <strong> </ strong>.";

/ / Adduser
$ PMF_LANG ["ad_adus_adduser"] = "Añadir usuario";
$ PMF_LANG ["ad_adus_name"] = "Nombre:";
$ PMF_LANG ["ad_adus_password"] = "Contraseña:";
$ PMF_LANG ["ad_adus_add"] = "Añadir usuario";
$ PMF_LANG ["ad_adus_suc"] = "El usuario <strong> éxito </ strong> agregó.";
$ PMF_LANG ["ad_adus_edit"] = "Editar perfil";
$ PMF_LANG ["ad_adus_dberr"] = "error de base de datos <strong> </ strong>";
$ PMF_LANG ["ad_adus_exerr"] = "Nombre de usuario <strong> existe </ strong> ya.";

/ / Sesiones
$ PMF_LANG ["ad_sess_id"] = "ID";
$ PMF_LANG ["ad_sess_sid"] = "ID de la sesión";
$ PMF_LANG ["ad_sess_ip"] = "IP";
$ PMF_LANG ["ad_sess_time"] = "Tiempo";
$ PMF_LANG ["ad_sess_pageviews"] = "PageViews";
$ PMF_LANG ["ad_sess_search"] = "Buscar";
$ PMF_LANG ["ad_sess_sfs"] = "Búsqueda de sesiones";
$ PMF_LANG ["ad_sess_s_ip"] = "IP";
$ PMF_LANG ["ad_sess_s_minct"] = "min acciones:.";
$ PMF_LANG ["ad_sess_s_date"] = "Fecha";
$ PMF_LANG ["ad_sess_s_after"] = "después";
$ PMF_LANG ["ad_sess_s_before"] = "antes";
$ PMF_LANG ["ad_sess_s_search"] = "Buscar";
$ PMF_LANG ["ad_sess_session"] = "sesión";
$ PMF_LANG ["ad_sess_r"] = "Resultados de la búsqueda para";
$ PMF_LANG ["ad_sess_referer"] = "Referer:";
$ PMF_LANG ["ad_sess_browser"] = "Navegador";
$ PMF_LANG ["ad_sess_ai_rubrik"] = "Categoría";
$ PMF_LANG ["ad_sess_ai_artikel"] = "Registro";
$ PMF_LANG ["ad_sess_ai_sb"] = "Buscar Cadenas:";
$ PMF_LANG ["ad_sess_ai_sid"] = "ID de la sesión:";
$ PMF_LANG ["ad_sess_back"] = "Volver";

/ / Statistik
$ PMF_LANG ["ad_rs"] = "Estadísticas de Calificación";
$ PMF_LANG ["ad_rs_rating_1"] = "El ranking de los";
$ PMF_LANG ["ad_rs_rating_2"] = "muestra a los usuarios:";
$ PMF_LANG ["ad_rs_red"] = "rojo";
$ PMF_LANG ["ad_rs_green"] = "Verde";
$ PMF_LANG ["ad_rs_altt"] = "con una media inferior a 2";
$ PMF_LANG ["ad_rs_ahtf"] = "con un promedio superior a 4";
$ PMF_LANG ["ad_rs_no"] = "No hay clasificación disponible";

/ / Autenticación
$ PMF_LANG ["ad_auth_insert"] = "Por favor, introduzca su nombre de usuario y una contraseña.";
$ PMF_LANG ["ad_auth_user"] = "Nombre de usuario:";
$ PMF_LANG ["ad_auth_passwd"] = "Contraseña:";
$ PMF_LANG ["ad_auth_ok"] = "OK";
$ PMF_LANG ["ad_auth_reset"] = "Reset";
$ PMF_LANG ["ad_auth_fail"] = "El usuario o la contraseña no es válida.";
$ PMF_LANG ["ad_auth_sess"] = "El ID de sesión se pasa.";

/ / Añadido v0.8 - 24.05.2001 - Bastian - Admin
$ PMF_LANG ["ad_config_edit"] = "Editar configuración";
$ PMF_LANG ["ad_config_save"] = "Guardar configuración";
$ PMF_LANG ["ad_config_reset"] = "Reset";
$ PMF_LANG ["ad_config_saved"] = "La configuración se ha guardado correctamente.";
$ PMF_LANG ["ad_menu_editconfig"] = "Editar configuración";
$ PMF_LANG ["ad_att_none"] = "No tiene archivos disponibles";
$ PMF_LANG ["ad_att_att"] = "Archivos adjuntos";
$ PMF_LANG ["ad_att_add"] = "Adjuntar archivos";
$ PMF_LANG ["ad_entryins_suc"] = "Registro guardado con éxito.";
$ PMF_LANG ["ad_entryins_fail"] = "Ha ocurrido un error.";
$ PMF_LANG ["ad_att_del"] = "Eliminar";
$ PMF_LANG ["ad_att_nope"] = "Archivos adjuntos sólo pueden ser añadidos durante la edición.";
$ PMF_LANG ["ad_att_delsuc"] = "El archivo adjunto se ha eliminado correctamente.";
$ PMF_LANG ["ad_att_delfail"] = "Ha ocurrido un error al eliminar el archivo adjunto.";
$ PMF_LANG ["ad_entry_add"] = "Añadir FAQ";

/ / Añadido v0.85 - 08.06.2001 - Bastian - Admin
$ PMF_LANG ["ad_csv_make"] = "Una copia de seguridad es una imagen completa del contenido de la base de datos. El formato de la copia de seguridad es un archivo de transacciones de SQL, que puede ser importado usando herramientas como phpMyAdmin o la línea de comandos del cliente SQL. Una copia de seguridad se debe realizar por lo menos una vez al mes ".;
$ PMF_LANG ["ad_csv_link"] = "Descargar la copia de seguridad";
$ PMF_LANG ["ad_csv_head"] = "Crear una copia de seguridad";
$ PMF_LANG ["ad_att_addto"] = "Agregar un archivo adjunto con el tema";
$ PMF_LANG ["ad_att_addto_2"] = "";
$ PMF_LANG ["ad_att_att"] = "Archivo:";
$ PMF_LANG ["ad_att_butt"] = "OK";
$ PMF_LANG ["ad_att_suc"] = "El archivo ha sido conectado con éxito.";
$ PMF_LANG ["ad_att_fail"] = "Ocurrió un error al adjuntar el archivo.";
$ PMF_LANG ["ad_att_close"] = "Cerrar esta ventana";

/ / Añadido v0.85 - 08.07.2001 - Bastian - Admin
$ PMF_LANG ["ad_csv_restore"] = "Con este formulario, usted puede restaurar el contenido de la base de datos, utilizando una copia de seguridad realizada con phpMyFAQ Tenga en cuenta que los datos existentes se sobrescribirán..";
$ PMF_LANG ["ad_csv_file"] = "Archivo";
$ PMF_LANG ["ad_csv_ok"] = "OK";
$ PMF_LANG ["ad_csv_linklog"] = "archivos de registro de copia de seguridad";
$ PMF_LANG ["ad_csv_linkdat"] = "Los datos de copia de seguridad";
$ PMF_LANG ["ad_csv_head2"] = "Restaurar";
$ PMF_LANG ["ad_csv_no"] = "Esto no parece ser una copia de seguridad de phpMyFAQ.";
$ PMF_LANG ["ad_csv_prepare"] = "Preparación de las consultas de base de datos ...";
$ PMF_LANG ["ad_csv_process"] = "Consulta ...";
$ PMF_LANG ["ad_csv_of"] = "de";
$ PMF_LANG ["ad_csv_suc"] = "fueron un éxito.";
$ PMF_LANG ["ad_csv_backup"] = "Copia de seguridad";
$ PMF_LANG ["ad_csv_rest"] = "Restaurar una copia de seguridad";

/ / Añadido v0.8 - 25.05.2001 - Bastian - Admin
$ PMF_LANG ["ad_menu_backup"] = "Copia de seguridad";
$ PMF_LANG ["ad_logout"] = "Sesión terminado con éxito.";
$ PMF_LANG ["ad_news_add"] = "Añadir noticias";
$ PMF_LANG ["ad_news_edit"] = "Editar noticias";
$ PMF_LANG ["ad_cookie"] = "Cookies";
$ PMF_LANG ["ad_sess_head"] = "Ver sesiones";

/ / Añadido v1.1 - 06.01.2002 - Bastian
$ PMF_LANG ["ad_menu_categ_edit"] = "Categorías";
$ PMF_LANG ["ad_menu_stat"] = "Estadísticas de Calificación";
$ PMF_LANG ["ad_kateg_add"] = "Añadir categoría principal";
$ PMF_LANG ["ad_kateg_rename"] = "Cambiar nombre";
$ PMF_LANG ["ad_adminlog_date"] = "Fecha";
$ PMF_LANG ["ad_adminlog_user"] = "Usuario";
$ PMF_LANG ["ad_adminlog_ip"] = "Dirección IP";

$ PMF_LANG ["ad_stat_sess"] = "Sessions";
$ PMF_LANG ["ad_stat_days"] = "Días";
$ PMF_LANG ["ad_stat_vis"] = "Sesiones (visitas)";
$ PMF_LANG ["ad_stat_vpd"] = "Visitas por Día";
$ PMF_LANG ["ad_stat_fien"] = "Registro de Primera";
$ PMF_LANG ["ad_stat_laen"] = "último registro";
$ PMF_LANG ["ad_stat_browse"] = "Sesiones de buscar";
$ PMF_LANG ["ad_stat_ok"] = "OK";

$ PMF_LANG ["ad_sess_time"] = "Tiempo";
$ PMF_LANG ["ad_sess_sid"] = "session-id";
$ PMF_LANG ["ad_sess_ip"] = "Dirección IP";

$ PMF_LANG ["ad_ques_take"] = "Tomar pregunta y edición";
$ PMF_LANG ["no_cats"] = "No se encuentran categorías.";

/ / Añadido v1.1 - 17.01.2002 - Bastian
$ PMF_LANG ["ad_log_lger"] = "usuario o contraseña no válidos.";
$ PMF_LANG ["ad_log_sess"] = "sesión ha expirado.";
$ PMF_LANG ["ad_log_edit"] = "\" Editar Usuario \ "-Formulario para el siguiente usuario:";
$ PMF_LANG ["ad_log_crea"] = "\" El nuevo artículo \ "forma".;
$ PMF_LANG ["ad_log_crsa"] = "Nueva entrada creada.";
$ PMF_LANG ["ad_log_ussa"] = "Actualización de datos para el usuario:";
$ PMF_LANG ["ad_log_usde"] = "Se eliminó el siguiente usuario:";
$ PMF_LANG ["ad_log_beed"] = "Editar forma para el siguiente usuario:";
$ PMF_LANG ["ad_log_bede"] = "Se eliminó la siguiente entrada:";

$ PMF_LANG ["ad_start_visits"] = "Visitas";
$ PMF_LANG ["ad_start_articles"] = "Artículos";
$ PMF_LANG ["ad_start_comments"] = "Comentarios";


/ / Añadido v1.1 - 30.01.2002 - Bastian
$ PMF_LANG ["ad_categ_paste"] = "paste";
$ PMF_LANG ["ad_categ_cut"] = "corte";
$ PMF_LANG ["ad_categ_copy"] = "copia";
$ PMF_LANG ["ad_categ_process"] = "categorías de procesamiento ...";

/ / Añadido v1.1.4 - 07.05.2002 - Thorsten
$ PMF_LANG ["err_NotAuth"] = "<strong> Usted no está autorizado </ strong>.";

/ / Añadido v1.2.3 - 29.11.2002 - Thorsten
$ PMF_LANG ["msgPreviusPage"] = "página anterior";
$ PMF_LANG ["msgNextPage"] = "página siguiente";
$ PMF_LANG ["msgPageDoublePoint"] = "de la página:";
$ PMF_LANG ["msgMainCategory"] = "categoría principal";

/ / Añadido v1.2.4 - 30.01.2003 - Thorsten
$ PMF_LANG ["ad_passwdsuc"] = "Su contraseña ha sido cambiada.";

/ / Añadido v1.3.0 - 04.03.2003 - Thorsten
$ PMF_LANG ["msgPDF"] = "Mostrar como fichero PDF";
$ PMF_LANG ["ad_xml_head"] = "XML-Copia de seguridad";
$ PMF_LANG ["ad_xml_hint"] = "Guardar todos los registros de su FAQ en un archivo XML.";
$ PMF_LANG ["ad_xml_gen"] = "Crear archivo XML";
$ PMF_LANG ["ad_entry_locale"] = "Lenguaje";
$ PMF_LANG ["msgLangaugeSubmit"] = "Seleccionar idioma";

/ / Añadido v1.3.1 - 29.04.2003 - Thorsten
$ PMF_LANG ["ad_entry_preview"] = "Vista previa";
$ PMF_LANG ["ad_attach_1"] = "Por favor, elija un directorio para archivos adjuntos por primera vez en la configuración.";
$ PMF_LANG ["ad_attach_2"] = "Por favor, elija un enlace de archivos adjuntos por primera vez en la configuración.";
$ PMF_LANG ["ad_attach_3"] = "El attachment.php archivo no puede abrirse sin la autentificación apropiada.";
$ PMF_LANG ["ad_attach_4"] = "El archivo adjunto debe ser menor que% s bytes.";
$ PMF_LANG ["ad_menu_export"] = "Exportar el FAQ";
$ PMF_LANG ["ad_export_1"] = "Built-Feed RSS de";
$ PMF_LANG ["ad_export_2"] = ".";
$ PMF_LANG ["ad_export_file"] = "Error: No se puede escribir el archivo.";
$ PMF_LANG ["ad_export_news"] = "Noticias RSS-Feed";
$ PMF_LANG ["ad_export_topten"] = "Top 10 RSS-Feed";
$ PMF_LANG ["ad_export_latest"] = "5 últimos registros RSS-Feed";
$ PMF_LANG ["ad_export_pdf"] = "PDF-exportación de todos los registros";
$ PMF_LANG ["ad_export_generate"] = "construir RSS-Feed";

$ PMF_LANG ["rightsLanguage"] ['adduser'] = "Añadir usuario";
$ PMF_LANG ["rightsLanguage"] ["edituser '] =" Editar usuario ";
$ PMF_LANG ["rightsLanguage"] ["deluser '] =" Eliminar el usuario ";
$ PMF_LANG ["rightsLanguage"] ["addbt '] =" Agregar ";
$ PMF_LANG ["rightsLanguage"] ["editbt '] =" Editar registro ";
$ PMF_LANG ["rightsLanguage"] ["delbt '] =" Eliminar registro ";
$ PMF_LANG ["rightsLanguage"] ["viewlog '] =" Ver registro ";
$ PMF_LANG ["rightsLanguage"] ["adminlog '] =" log admin vista ";
$ PMF_LANG ["rightsLanguage"] ["delcomment '] =" eliminar comentario ";
$ PMF_LANG ["rightsLanguage"] ["addnews '] =" Añadir noticias ";
$ PMF_LANG ["rightsLanguage"] ["editnews '] =" Editar noticias ";
$ PMF_LANG ["rightsLanguage"] ["delnews '] =" borrar noticias ";
$ PMF_LANG ["rightsLanguage"] ["addcateg '] =" Agregar categoría ";
$ PMF_LANG ["rightsLanguage"] ["editcateg '] =" Editar categoría ";
$ PMF_LANG ["rightsLanguage"] ["delcateg '] =" eliminar la categoría ";
$ PMF_LANG ["rightsLanguage"] ['password'] = "Cambiar contraseña";
$ PMF_LANG ["rightsLanguage"] ["editconfig '] =" Editar configuración ";
$ PMF_LANG ["rightsLanguage"] ["addatt '] =" Agregar archivos adjuntos ";
$ PMF_LANG ["rightsLanguage"] ["delatt '] =" borrar archivos adjuntos ";
$ PMF_LANG ["rightsLanguage"] ['backup'] = "Crear copia de seguridad";
$ PMF_LANG ["rightsLanguage"] ["restaurar"] = "Restaurar copia de seguridad";
$ PMF_LANG ["rightsLanguage"] ["delquestion '] =" eliminar preguntas abiertas ";
$ PMF_LANG ["rightsLanguage"] ["changebtrevs '] =" revisiones de edición ";

$ PMF_LANG ["msgAttachedFiles"] = "archivos adjuntos";

/ / Añadido v1.3.3 - 27.05.2003 - Thorsten
$ PMF_LANG ["ad_user_action"] = "acción";
$ PMF_LANG ["ad_entry_email"] = "Dirección de correo electrónico:";
$ PMF_LANG ["ad_entry_allowComments"] = "Permitir comentarios";
$ PMF_LANG ["msgWriteNoComment"] = "No se puede comentar este registro";
$ PMF_LANG ["ad_user_realname"] = "Nombre real:";
$ PMF_LANG ["ad_export_generate_pdf"] = "generar el archivo PDF";
$ PMF_LANG ["ad_export_full_faq"] = "Tu FAQ como un archivo PDF:";
$ PMF_LANG ["err_bannedIP"] = "Tu dirección IP ha sido prohibido.";
$ PMF_LANG ["err_SaveQuestion"] = "Los campos obligatorios están <strong> su nombre </ strong>, <strong> su dirección de correo electrónico </ strong>, <strong> su pregunta </ ??strong> y, cuando lo solicite, el <fuerte > <a href=\"http://en.wikipedia.org/wiki/Captcha\" title=\"Read más sobre Captcha Captcha en Wikipedia\" target=\"_blank\"> </ a> código </ strong> <br /> <br /> <a href=\"javascript:history.back();\"> una última página </ a> <br /> <br /> ".;

/ / Añade v1.3.4 - 23.07.2003 - Thorsten
$ PMF_LANG ["ad_entry_fontcolor"] = "Color de la fuente:";
$ PMF_LANG ["ad_entry_fontsize"] = "Tamaño de fuente:";

/ / Añade v1.4.0 - 12/04/2003 por Thorsten / Mathias
$ LANG_CONF ['main.language'] = array (0 => "seleccionar", 1 => "El lenguaje-File");
$ LANG_CONF ["main.languageDetection"] = array (0 => "checkbox", 1 => "Permitir la negociación de contenido automático");
$ LANG_CONF ['main.titleFAQ'] = array (0 => "input", 1 => "Título de la FAQ");
$ LANG_CONF ['main.currentVersion'] = array (0 => "print", 1 => "Version FAQ");
$ LANG_CONF ["main.metaDescription"] = array (0 => "input", 1 => "Descripción de la página");
$ LANG_CONF ["main.metaKeywords"] = array (0 => "input", 1 => "Palabras clave de las arañas");
$ LANG_CONF ["main.metaPublisher"] = array (0 => "input", 1 => "Nombre de la Editorial");
$ LANG_CONF ['main.administrationMail'] = array (0 => "input", 1 => "Dirección de correo electrónico del administrador");
$ LANG_CONF ["main.contactInformations"] = array (0 => "area", 1 => "La información de contacto");
$ LANG_CONF ["main.send2friendText"] = array (0 => "area", 1 => "Texto para la página send2friend");
$ LANG_CONF ['main.maxAttachmentSize'] = array (0 => "input", 1 => "Tamaño máximo de archivos adjuntos en Bytes (max.% SByte)");
$ LANG_CONF ["main.disableAttachments"] = array (0 => "checkbox", 1 => "Vincular los adjuntos por debajo de las entradas?");
$ LANG_CONF ["main.enableUserTracking"] = array (0 => "checkbox", 1 => "El uso de seguimiento?");
$ LANG_CONF ["main.enableAdminLog"] = array (0 => "checkbox", 1 => "Adminlog usar?");
$ LANG_CONF ["main.ipCheck"] = array (0 => "checkbox", 1 => "¿Quieres que el IP que se comprueba cuando el control de la UIN en admin.php?");
$ LANG_CONF ["main.numberOfRecordsPerPage"] = array (0 => "input", 1 => "Cantidad de mensajes por página");
$ LANG_CONF ["main.numberOfShownNewsEntries"] = array (0 => "input", 1 => "El número de artículos de noticias");
$ LANG_CONF ['main.bannedIPs'] = array (0 => "area", 1 => "Prohibición de estas direcciones IP");
$ LANG_CONF ["main.enableRewriteRules"] = array (0 => "checkbox", 1 => "Activar el soporte mod_rewrite (por defecto: desactivado)");
$ LANG_CONF ["main.ldapSupport"] = array (0 => "checkbox", 1 => "¿Quiere habilitar el soporte de LDAP (por defecto: desactivado)");
$ LANG_CONF ["main.referenceURL"] = array (0 => "input", 1 => "URL base para la verificación de enlace (por ejemplo: http://www.example.org/faq)");
$ LANG_CONF ["main.urlValidateInterval"] = array (0 => "input", 1 => "Intervalo entre el enlace de verificación AJAX (en segundos)");
$ LANG_CONF ["records.enableVisibilityQuestions"] = array (0 => "checkbox", 1 => "Desactivar la visibilidad de nuevas preguntas?");
$ LANG_CONF ['main.permLevel'] = array (0 => "seleccionar", 1 => "al nivel de permiso");

$ PMF_LANG ["ad_categ_new_main_cat"] = "como nueva categoría principal";
$ PMF_LANG ["ad_categ_paste_error"] = "Mover esta categoría no es posible.";
$ PMF_LANG ["ad_categ_move"] = "categoría de movimiento";
$ PMF_LANG ["ad_categ_lang"] = "Lenguaje";
$ PMF_LANG ["ad_categ_desc"] = "Descripción";
$ PMF_LANG ["ad_categ_change"] = "Cambiar con";

$ PMF_LANG ["Lostpassword"] = "Ha olvidado su contraseña?.";
$ PMF_LANG ["lostpwd_err_1"] = "Error:. Nombre y dirección de correo electrónico no encontrado";
$ PMF_LANG ["lostpwd_err_2"] = "Error: Las entradas incorrectas";
$ PMF_LANG ["lostpwd_text_1"] = "Gracias por solicitar información de su cuenta.";
$ PMF_LANG ["lostpwd_text_2"] = "Por favor, establecer una nueva contraseña personal en la sección de administración de su municipio.";
$ PMF_LANG ["lostpwd_mail_okay"] = "El correo electrónico fue enviado.";

$ PMF_LANG ["ad_xmlrpc_button"] = "Obtener último número de la versión phpMyFAQ por el servicio web";
$ PMF_LANG ["ad_xmlrpc_latest"] = "La última versión disponible en";

/ / Añade v1.5.0 - 07/31/2005 por Thorsten
$ PMF_LANG ['ad_categ_select'] = 'idioma Seleccionar categoría';

/ / Añade v1.5.1 - 09/06/2005 por Thorsten
$ PMF_LANG ['msgSitemap'] = 'Mapa del sitio ";

/ / Añade v1.5.2 - 23/09/2005 por Lars
$ PMF_LANG ['err_inactiveArticle'] = 'Esta entrada se encuentra en revisión y no se puede mostrar.';
$ PMF_LANG ['msgArticleCategories'] = 'Categorías de esta entrada;

/ / Añade v1.6.0 - 02/02/2006 por Thorsten
$ PMF_LANG ['ad_entry_solution_id'] = 'ID única solución ";
$ PMF_LANG ['ad_entry_faq_record'] = 'registro FAQ';
$ PMF_LANG ['ad_entry_new_revision'] = 'Crear nueva revisión? ";
$ PMF_LANG ['ad_entry_record_administration'] = 'Administración de registros;
$ PMF_LANG ['ad_entry_changelog'] = 'cambios';
$ PMF_LANG ['ad_entry_revision'] = 'revisiones';
$ PMF_LANG ['ad_changerev'] = 'Revisiones Seleccione';
$ PMF_LANG ['msgCaptcha'] = "Por favor, ingrese los caracteres que está leyendo en la imagen";
$ PMF_LANG ['msgSelectCategories'] = 'Buscar en ...';
$ PMF_LANG ['msgAllCategories'] = '... ; todas las categorías "
$ PMF_LANG ['ad_you_should_update'] = 'Tu instalación phpMyFAQ no está actualizado. Usted debe actualizar a la última versión disponible.;
$ PMF_LANG ['msgAdvancedSearch'] = "Búsqueda avanzada";

/ / Añade v1.6.1 - 25/04/2006 por Matteoï ¿½ andi ¿½ Thorsten
$ PMF_LANG ['spamControlCenter'] = 'Spam centro de control';
$ LANG_CONF ["spam.enableSafeEmail"] = array (0 => "checkbox", 1 => "El correo electrónico del usuario de impresión de forma segura (por defecto: activado ).");
$ LANG_CONF ["spam.checkBannedWords"] = array (0 => "checkbox", 1 => "Comprobar el contenido de forma pública en contra de las palabras prohibidas (por defecto: activado ).");
$ LANG_CONF ["spam.enableCaptchaCode"] = array (0 => "checkbox", 1 => "Usar un código captcha para permitir el envío forma pública (por defecto: activado ).");
$ PMF_LANG ['ad_session_expiring'] = 'Su sesión expirará en% d minutos: le gustaría seguir trabajando ";

/ / Añade v1.6.2 - 06/13/2006 por Matteo
$ PMF_LANG ['ad_stat_management'] = 'la gestión de sesiones;
$ PMF_LANG ['ad_stat_choose'] = 'Elija el mes;
$ PMF_LANG ['ad_stat_delete'] = 'Eliminar inmediatamente las sesiones seleccionadas;

/ / Añade v2.0.0 - 15/09/2005 por Thorsten y por Minoru TODA
$ PMF_LANG ['ad_menu_glossary'] = 'Glosario';
$ PMF_LANG ['ad_glossary_add'] = "Añadir entrada en el glosario;
$ PMF_LANG ['ad_glossary_edit'] = 'Editar entrada en el glosario;
$ PMF_LANG ['ad_glossary_item'] = 'Tema';
$ PMF_LANG ['ad_glossary_definition'] = 'Definición';
$ PMF_LANG ['ad_glossary_save'] = 'Guardar la entrada;
$ PMF_LANG ['ad_glossary_save_success'] = 'entrada en el glosario guardado con éxito';
$ PMF_LANG ['ad_glossary_save_error'] = 'La entrada en el glosario no podía guardar debido a un error.';
$ PMF_LANG ['ad_glossary_update_success'] = 'entrada en el glosario actualizado correctamente';
$ PMF_LANG ['ad_glossary_update_error'] = 'La entrada en el glosario no pudo actualizar debido a un error.';
$ PMF_LANG ['ad_glossary_delete'] = "Eliminar entrada";
$ PMF_LANG ['ad_glossary_delete_success'] = 'entrada en el glosario borrado con éxito';
$ PMF_LANG ['ad_glossary_delete_error'] = 'La entrada en el glosario no se ha podido eliminar debido a un error.';
$ PMF_LANG ['ad_linkcheck_noReferenceURL'] = 'el enlace de verificación automática de discapacitados (URL base para el enlace de verificación no establecido);
$ PMF_LANG ['ad_linkcheck_noAllowUrlOpen'] = 'desactivado verificación automática del enlace (PHP allow_url_fopen opción no está activada);
$ PMF_LANG ['ad_linkcheck_checkResult'] = 'resultado de la verificación automática de enlace;
$ PMF_LANG ['ad_linkcheck_checkSuccess'] = 'OK';
$ PMF_LANG ['ad_linkcheck_checkFailed'] = 'Error';
$ PMF_LANG ['ad_linkcheck_failReason'] = 'Razón (s) failed:';
$ PMF_LANG ['ad_linkcheck_noLinksFound'] = 'No hay direcciones URL compatible con la función de enlace verificador encontró.';
$ PMF_LANG ['ad_linkcheck_searchbadonly'] = 'Sólo con enlaces malos ";
$ PMF_LANG ['ad_linkcheck_infoReason'] = 'Información Adicional:';
$ PMF_LANG ['ad_linkcheck_openurl_infoprefix'] = 'encontrados durante las pruebas <strong>% s </ strong>:';
$ PMF_LANG ['ad_linkcheck_openurl_notready'] = 'No LinkVerifier listo. ";
$ PMF_LANG ['ad_linkcheck_openurl_maxredirect'] = 'Máximo redirigir contar <strong>% d </ strong> superado. ";
$ PMF_LANG ['ad_linkcheck_openurl_urlisblank'] = 'Se resuelve a la dirección URL en blanco.';
$ PMF_LANG ['ad_linkcheck_openurl_tooslow'] = 'Host% <strong> s </ strong> es lento o no responde. ";
$ PMF_LANG ['ad_linkcheck_openurl_nodns'] = 'la resolución de DNS de servidor% s <strong> </ strong> es lenta o no es debido a problemas de DNS, local o remoto.';
$ PMF_LANG ['ad_linkcheck_openurl_redirected'] = 'URL fue redirigida a% <strong> s </ strong>.';
$ PMF_LANG ['ad_linkcheck_openurl_ambiguous'] = 'ambiguo estatus HTTP <strong>% s </ strong> devueltos. ";
$ PMF_LANG ['ad_linkcheck_openurl_not_allowed'] = "La cabeza <em> </ em> método no es compatible con el anfitrión% <strong> s </ strong>, los métodos permitidos:. <strong>% S </ strong> ';
$ PMF_LANG ['ad_linkcheck_openurl_not_found'] = 'Este recurso no se puede encontrar en el servidor% s <strong> </ strong>.';
$ PMF_LANG ['ad_linkcheck_protocol_unsupported'] = 'no soportado por el protocolo de verificación automática de enlace% s.';
$ PMF_LANG ['ad_menu_linkconfig'] = 'Verificador de URL;
$ PMF_LANG ['ad_linkcheck_config_title'] = 'Configuración del Verificador de URL;
$ PMF_LANG ['ad_linkcheck_config_disabled'] = 'función de verificador de URL con discapacidad;
$ PMF_LANG ['ad_linkcheck_config_warnlist'] = 'URL para advertir ";
$ PMF_LANG ['ad_linkcheck_config_ignorelist'] = 'URL de ignorar;
$ PMF_LANG ['ad_linkcheck_config_warnlist_description'] = 'URL con el prefijo artículos a continuación se publicará aviso, independientemente de si es válido <br /> Utilice esta función para detectar pronto-a-ser URL difunto.';
$ PMF_LANG ['ad_linkcheck_config_ignorelist_description'] = 'URL exacta se enumeran a continuación serán asumidos válido sin validación <br /> Utilice esta función para omitir las direcciones URL que no para validar el uso Verificador de URL.';
$ PMF_LANG ['ad_linkcheck_config_th_id'] = 'ID #';
$ PMF_LANG ['ad_linkcheck_config_th_url'] = 'URL para que coincida con';
$ PMF_LANG ['ad_linkcheck_config_th_reason'] = 'la razón del partido;
$ PMF_LANG ['ad_linkcheck_config_th_owner'] = 'dueño de entrada ";
$ PMF_LANG ['ad_linkcheck_config_th_enabled'] = 'Set para permitir la entrada;
$ PMF_LANG ['ad_linkcheck_config_th_locked'] = 'la posición de bloqueo de propiedad;
$ PMF_LANG ['ad_linkcheck_config_th_chown'] = 'Set para obtener la propiedad ";
$ PMF_LANG ['msgNewQuestionVisible'] = 'La pregunta tiene que ser revisado antes de conseguir público.';
$ PMF_LANG ['msgQuestionsWaiting'] = 'Esperando a la publicación por los administradores:';
$ PMF_LANG ['ad_entry_visibility'] = 'Publicar';

/ / Añade v2.0.0 - 02/01/2006 por Lars
$ PMF_LANG ['ad_user_error_password'] = "Por favor, introduzca una contraseña.";
$ PMF_LANG ['ad_user_error_passwordsDontMatch'] = "Las contraseñas no coinciden.";
$ PMF_LANG ['ad_user_error_loginInvalid'] = "El nombre de usuario especificado no es válido.";
$ PMF_LANG ['ad_user_error_noEmail'] = "Por favor, introduzca una dirección de correo válida.";
$ PMF_LANG ['ad_user_error_noRealName'] = "Por favor, introduzca su nombre real.";
$ PMF_LANG ['ad_user_error_delete'] = "Cuenta de usuario no pudo ser eliminado.";
$ PMF_LANG ['ad_user_error_noId'] = "No ID especificado.";
$ PMF_LANG ['ad_user_error_protectedAccount'] = "Cuenta de usuario está protegido.";
$ PMF_LANG ['ad_user_deleteUser'] = "Eliminar usuario";
$ PMF_LANG ['ad_user_status'] = "Estado";
$ PMF_LANG ['ad_user_lastModified'] = "modificada por última vez:";
$ PMF_LANG ['ad_gen_cancel'] = "Cancelar";
$ PMF_LANG ["rightsLanguage"] ["addglossary '] =" añadir el artículo glosario ";
$ PMF_LANG ["rightsLanguage"] ["editglossary '] =" Editar elemento glosario ";
$ PMF_LANG ["rightsLanguage"] ["delglossary '] =" suprimir el punto glosario ";
$ PMF_LANG ["ad_menu_group_administration"] = "grupos";
$ PMF_LANG ['ad_user_loggedin'] = 'Conectado como';

$ PMF_LANG ['ad_group_details'] = "Detalles del grupo";
$ PMF_LANG ['ad_group_add'] = "Agregar grupo";
$ PMF_LANG ['ad_group_add_link'] = "Agregar grupo";
$ PMF_LANG ['AD_GROUP_NAME'] = "Nombre:";
$ PMF_LANG ['ad_group_description'] = "Descripción:";
$ PMF_LANG ['ad_group_autoJoin'] = "Auto-join:";
$ PMF_LANG ['ad_group_suc'] = "Grupo <strong> éxito </ strong> agregó.";
$ PMF_LANG ['ad_group_error_noName'] = "Por favor, introduzca un nombre de grupo.";
$ PMF_LANG ['ad_group_error_delete'] = "Grupo no pudo ser eliminado.";
$ PMF_LANG ['ad_group_deleted'] = "El grupo se ha eliminado correctamente.";
$ PMF_LANG ['ad_group_deleteGroup'] = "Eliminar grupo";
$ PMF_LANG ['ad_group_deleteQuestion'] = "¿Estás seguro de que este grupo se suprime?";
$ PMF_LANG ['ad_user_uncheckall'] = "Deseleccionar todo";
$ PMF_LANG ['ad_group_membership'] = "pertenencia a grupos";
$ PMF_LANG ['ad_group_members'] = "Miembros";
$ PMF_LANG ['ad_group_addMember'] = "+";
$ PMF_LANG ['ad_group_removeMember'] = "-";

/ / Añade v2.0.0 - 20/07/2006 por Matteo
$ PMF_LANG ['ad_export_which_cat'] = 'Límite de los datos de preguntas frecuentes para ser exportado (opcional);
$ PMF_LANG ['ad_export_cat_downwards'] = 'hacia abajo';
$ PMF_LANG ['ad_export_type'] = 'Formato de la exportación;
$ PMF_LANG ['ad_export_type_choose'] = 'Elija uno de los formatos soportados:';
$ PMF_LANG ['ad_export_download_view'] = 'Descargar o ver en línea? ";
$ PMF_LANG ['ad_export_download'] = 'download';
$ PMF_LANG ['ad_export_view'] = 'Ver en línea ";
$ PMF_LANG ['ad_export_gen_xhtml'] = 'todo tipo de archivos XHTML;
$ PMF_LANG ['ad_export_gen_docbook'] = 'todo tipo de archivos Docbook;

/ / Añade v2.0.0 - 22/07/2006 por Matteo
$ PMF_LANG ['ad_news_data'] = 'Noticias de datos';
$ PMF_LANG ['ad_news_author_name'] = 'Nombre del autor:';
$ PMF_LANG ['ad_news_author_email'] = 'e-mail autor:';
$ PMF_LANG ['ad_news_set_active'] = 'Activar:';
$ PMF_LANG ['ad_news_allowComments'] = 'Permitir comentarios:';
$ PMF_LANG ['ad_news_expiration_window'] = 'Noticias de expiración de tiempo (opcional);
$ PMF_LANG ['ad_news_from'] = "De:";
$ PMF_LANG ['ad_news_to'] = "Para:";
$ PMF_LANG ['ad_news_insertfail'] = 'Ha ocurrido un error de insertar la noticia en la base de datos.';
$ PMF_LANG ['ad_news_updatefail'] = 'Ha ocurrido un error de actualización de la noticia en la base de datos.';
$ PMF_LANG ['newsShowCurrent'] = 'Mostrar noticias actuales.';
$ PMF_LANG ['newsShowArchive'] = 'Mostrar archivo de noticias. ";
$ PMF_LANG ["Archivo de Noticias '] =' Archivo de noticias";
$ PMF_LANG ['newsWriteComment'] = 'comentario en esta entrada;
$ PMF_LANG ['newsCommentDate'] = 'Añadido a las:';

/ / Añade v2.0.0 - 07/29/2006 por Matteo y Thorsten
$ PMF_LANG ['ad_record_expiration_window'] = 'Registro de expiración de tiempo (opcional);
$ PMF_LANG ['admin_mainmenu_home'] = 'Dashboard';
$ PMF_LANG ['admin_mainmenu_users'] = 'Usuarios';
$ PMF_LANG ['admin_mainmenu_content'] = 'Contenido';
$ PMF_LANG ['admin_mainmenu_statistics'] = 'Estadísticas';
$ PMF_LANG ['admin_mainmenu_exports'] = 'Exportaciones';
$ PMF_LANG ['admin_mainmenu_backup'] = 'Copia de seguridad ";
$ PMF_LANG ['admin_mainmenu_configuration'] = 'Configuración';
$ PMF_LANG ['admin_mainmenu_logout'] = 'Cerrar sesión';

/ / Añade v2.0.0 - 15/08/2006 por Thorsten y Matteo
$ PMF_LANG ["ad_categ_owner"] = "dueño de la categoría ';
$ PMF_LANG ['adminSection'] = 'Administración';
$ PMF_LANG ['err_expiredArticle'] = 'Esta entrada ha caducado y no se puede mostrar ";
$ PMF_LANG ['err_expiredNews'] = 'Esta noticia ha caducado y no se puede mostrar ";
$ PMF_LANG ['err_inactiveNews'] = 'Esta noticia se encuentra en revisión y no se puede mostrar ";
$ PMF_LANG ['msgSearchOnAllLanguages'] = 'Buscar en todos los idiomas siguientes: ";
$ PMF_LANG ['ad_entry_tags'] = 'Etiquetas';
$ PMF_LANG ['msg_tags'] = 'Etiquetas';

/ / Añade v2.0.0 - 09/03/2006 por Matteo
$ PMF_LANG ['ad_linkcheck_feedback_url-batch1'] = 'Comprobando ...';
$ PMF_LANG ['ad_linkcheck_feedback_url-batch2'] = 'Comprobando ...';
$ PMF_LANG ['ad_linkcheck_feedback_url-batch3'] = 'Comprobando ...';
$ PMF_LANG ['ad_linkcheck_feedback_url de comprobación'] = 'Comprobando ...';
$ PMF_LANG ['ad_linkcheck_feedback_url discapacitados'] = 'reducida';
$ PMF_LANG ['ad_linkcheck_feedback_url-linkbad'] = 'Enlaces KO';
$ PMF_LANG ['ad_linkcheck_feedback_url-linkok'] = 'Enlaces OK';
$ PMF_LANG ['ad_linkcheck_feedback_url-NoAccess'] = 'No hay acceso;
$ PMF_LANG ['ad_linkcheck_feedback_url-noajax'] = 'No AJAX;
$ PMF_LANG ['ad_linkcheck_feedback_url-nolinks'] = 'No hay enlaces';
$ PMF_LANG ['ad_linkcheck_feedback_url-NoScript'] = 'No Guión';

/ / Añade v2.0.0 - 02/09/2006 por Thomas
$ PMF_LANG ['msg_related_articles'] = 'Entradas relacionadas';
$ LANG_CONF ['records.numberOfRelatedArticles'] = array (0 => "input", 1 => "El número de entradas relacionadas con");

/ / Añade v2.0.0 - 09/09/2006 por Rudi
$ PMF_LANG ['ad_categ_trans_1'] = 'Translate';
$ PMF_LANG ['ad_categ_trans_2'] = 'La categoría';
$ PMF_LANG ['ad_categ_translatecateg'] = 'Categoría Translate';
$ PMF_LANG ['ad_categ_translate'] = 'Translate';
$ PMF_LANG ['ad_categ_transalready'] = 'ya se ha traducido en:';
$ PMF_LANG ["ad_categ_deletealllang"] = 'Eliminar en todos los idiomas?';
$ PMF_LANG ["ad_categ_deletethislang"] = "Eliminar sólo en este idioma? ';
$ PMF_LANG ["ad_categ_translated"] = "La categoría ha sido traducido.";

/ / Añade v2.0.0 - 09/21/2006 por Rudi
$ PMF_LANG ["ad_categ_show"] = "Información general";
$ PMF_LANG ['ad_menu_categ_structure'] = "la vista de categorías incluyendo sus lenguas";

/ / Añade v2.0.0 - 26/09/2006 por Thorsten
$ PMF_LANG ['ad_entry_userpermission'] = 'Permisos de usuario:';
$ PMF_LANG ['ad_entry_grouppermission'] = 'permisos para grupos ";
$ PMF_LANG ['ad_entry_all_users'] = 'Acceso para todos los usuarios;
$ PMF_LANG ['ad_entry_restricted_users'] = 'Acceso restringido a';
$ PMF_LANG ['ad_entry_all_groups'] = 'Acceso para todos los grupos;
$ PMF_LANG ['ad_entry_restricted_groups'] = 'Acceso restringido a';
$ PMF_LANG ['ad_session_expiration'] = 'Tiempo de su expiración del período de sesiones;
$ PMF_LANG ['ad_user_active'] = 'activo';
$ PMF_LANG ['ad_user_blocked'] = "bloqueado";
$ PMF_LANG ['ad_user_protected'] = 'protegido';

/ / Añade v2.0.0 - 07/10/2006 por Matteo
$ PMF_LANG ['ad_entry_intlink'] = "Seleccione un registro de preguntas frecuentes para insertarlo como un vínculo ...';

/ / Añade 2.0.0 - 10/10/2006 por Rudi
$ PMF_LANG ["ad_categ_paste2"] = "Pegar después de";
$ PMF_LANG ["ad_categ_remark_move"] = "El intercambio de dos categorías sólo es posible en el mismo nivel.";
$ PMF_LANG ["ad_categ_remark_overview"] = "El orden correcto de las categorías se mostrará, si todas las categorías se definen por el lenguaje actual (primera columna).";

/ / Añade v2.0.0 - 15/10/2006 por Matteo
$ PMF_LANG ['msgUsersOnline'] = ':: Los huéspedes% d y% d registrados;
$ PMF_LANG ['ad_adminlog_del_older_30d'] = 'Eliminar inmediatamente los registros de más de 30 días;
$ PMF_LANG ['ad_adminlog_delete_success'] = 'registros anteriores se han eliminado correctamente.';
$ PMF_LANG ['ad_adminlog_delete_failure'] = 'No hay registros eliminados:. Ocurrido un error de realizar la solicitud;

/ / Añade 2.0.0 - 11/19/2006 por Thorsten
$ PMF_LANG ['opensearch_plugin_install'] = 'plugin de búsqueda agregar ";
$ PMF_LANG ['ad_quicklinks'] = 'Foros';
$ PMF_LANG ['ad_quick_category'] = 'Agregar una nueva categoría';
$ PMF_LANG ['ad_quick_record'] = 'Agregar nuevo registro FAQ';
$ PMF_LANG ['ad_quick_user'] = 'Agregar nuevo usuario';
$ PMF_LANG ['ad_quick_group'] = 'Agregar nuevo grupo';

/ / Añade v2.0.0 - 12/30/2006 por Matteo
$ PMF_LANG ['msgNewTranslationHeader'] = 'la propuesta de traducción ";
$ PMF_LANG ['msgNewTranslationAddon'] = 'Su propuesta no se publicará de inmediato, pero se dará a conocer por el moderador. Los campos obligatorios están <strong> su nombre </ strong>, <strong> su dirección de correo electrónico </ strong>, <strong> su traducción título </ strong> y <strong> su traducción preguntas frecuentes </ strong>. Por favor separa las palabras clave con un espacio único ".;
$ PMF_LANG ['msgNewTransSourcePane'] = 'panel principal ";
$ PMF_LANG ['msgNewTranslationPane'] = 'panel de traducción ";
$ PMF_LANG ['msgNewTranslationName'] = "Tu Nombre";
$ PMF_LANG ['msgNewTranslationMail'] = "Su dirección de correo electrónico:";
$ PMF_LANG ['msgNewTranslationKeywords'] = "Palabras claves:";
$ PMF_LANG ['msgNewTranslationSubmit'] = 'Enviar su propuesta;
$ PMF_LANG ['msgTranslate'] = 'Proponga una traducción para';
$ PMF_LANG ['msgTranslateSubmit'] = 'inicio de la traducción ...';
$ PMF_LANG ['msgNewTranslationThanks'] = "Gracias por su propuesta de traducción";

/ / Añade v2.0.0 - 27/02/2007 por Matteo
$ PMF_LANG ["rightsLanguage"] ["addgroup"] = "agregar cuentas de grupo";
$ PMF_LANG ["rightsLanguage"] ["editgroup '] =" Editar cuentas de grupo ";
$ PMF_LANG ["rightsLanguage"] ["delgroup '] =" eliminar las cuentas de grupo ";

/ / Añade v2.0.0 - 27/02/2007 por Thorsten
$ PMF_LANG ['ad_news_link_parent'] = 'El enlace se abre en ventana principal ";

/ / Añade v2.0.0 - 03/04/2007 por Thorsten
$ PMF_LANG ['ad_menu_comments'] = 'Comentarios';
$ PMF_LANG ['ad_comment_administration'] = 'Comentarios de administración;
$ PMF_LANG ['ad_comment_faqs'] = 'Comentarios en los registros de FAQ:';
$ PMF_LANG ['ad_comment_news'] = 'Comentarios en los registros de Noticias:';
$ PMF_LANG ['ad_groups'] = 'Grupos';

/ / Añade v2.0.0 - 03/10/2007 por Thorsten
$ LANG_CONF ['records.orderby'] = array (0 => 'select', 1 => 'Registro de clasificación (de acuerdo a la propiedad)');
$ LANG_CONF ['records.sortby'] = array (0 => 'select', 1 => 'Registro de clasificación (ascendente o descendente)');
$ PMF_LANG ['ad_conf_order_id'] = 'ID (por defecto);
$ PMF_LANG ['ad_conf_order_thema'] = 'Título';
$ PMF_LANG ['ad_conf_order_visits'] = 'El número de visitantes;
$ PMF_LANG ['ad_conf_order_datum'] = 'Fecha';
$ PMF_LANG ['ad_conf_order_author'] = 'Autor';
$ PMF_LANG ['ad_conf_desc'] = 'descendente';
$ PMF_LANG ['ad_conf_asc'] = 'ascendente';
$ PMF_LANG ['mainControlCenter'] = 'Configuración Principal';
$ PMF_LANG ['recordsControlCenter'] = 'Preguntas frecuentes configuración de los registros;

/ / Añade v2.0.0 - 03/17/2007 por Thorsten
$ PMF_LANG ['msgInstantResponse'] = 'Respuesta inmediata';
$ PMF_LANG ['msgInstantResponseMaxRecords'] = '. A continuación, encontrará los primeros registros% d '.;

/ / Añade v2.0.0 - 29/03/2007 por Thorsten
$ LANG_CONF ['records.defaultActivation'] = array (0 => "checkbox", 1 => "Activar un nuevo registro (por defecto: desactivado)");
$ LANG_CONF ['records.defaultAllowComments'] = array (0 => "checkbox", 1 => "Permitir comentarios para los nuevos registros (por defecto: no permitidos)");

/ / Añade v2.0.0 - 04/04/2007 por Thorsten
$ PMF_LANG ['msgAllCatArticles'] = 'Los registros de esta categoría';
$ PMF_LANG ['msgDescriptionInstantResponse'] = 'Sólo tienes que escribir y encontrar las respuestas ...';
$ PMF_LANG ['msgTagSearch'] = 'entradas con la etiqueta';
$ PMF_LANG ['ad_pmf_info'] = 'Información phpMyFAQ;
$ PMF_LANG ['ad_online_info'] = 'verificación de la versión en línea;
$ PMF_LANG ['ad_system_info'] = 'Información del sistema';

/ / Añade 2.5.0-alfa - 25/01/2008 por Elger
$ PMF_LANG ['msgRegisterUser'] = 'Registrar';
$ PMF_LANG ["ad_user_loginname"] = 'Nombre de usuario:';
$ PMF_LANG ['errorRegistration'] = 'Este campo es obligatorio!';
$ PMF_LANG ['submitRegister'] = 'Registrar';
$ PMF_LANG ['msgUserData'] = 'La información de usuario requeridos para el registro;
$ PMF_LANG ['captchaError'] = 'Por favor introduce el código captcha derecho';
$ PMF_LANG ['msgRegError'] = 'producido los siguientes errores. Por favor, corrija mismos: »;
$ PMF_LANG ['successMessage'] = 'El registro se realizó correctamente. Usted pronto recibirá un correo de confirmación con los datos de su nombre de usuario ';
$ PMF_LANG ['msgRegThankYou'] = 'Gracias por su inscripción;
$ PMF_LANG ['emailRegSubject'] = '[%% sitename] Registro: nuevo usuario';

/ / Añade 2.5.0-alfa 2 - 01/24/2009 por Thorsten
$ PMF_LANG ['msgMostPopularSearches'] = 'Las búsquedas más populares son: ";
$ LANG_CONF ['main.enableWysiwygEditor'] = array (0 => "checkbox", 1 => "Activar el paquete editor WYSIWYG (por defecto: activado)");

/ / Añade 2.5.0-beta - 03/30/2009 por Anatoliy
$ PMF_LANG ['ad_menu_searchstats'] = 'Estadísticas de la búsqueda';
$ PMF_LANG ['ad_searchstats_search_term'] = 'Palabra clave';
$ PMF_LANG ['ad_searchstats_search_term_count'] = 'count';
$ PMF_LANG ['ad_searchstats_search_term_lang'] = 'Lenguaje';
$ PMF_LANG ['ad_searchstats_search_term_percentage'] = "Porcentaje";

/ / Añade 2.5.0-beta - 31/03/2009 por Anatoliy
$ PMF_LANG ['ad_record_sticky'] = 'Sticky';
$ PMF_LANG ['ad_entry_sticky'] = 'Sticky';
$ PMF_LANG ['stickyRecordsHeader'] = 'Sticky Preguntas frecuentes ";

/ / Añade 2.5.0-beta - 04/01/2009 por Anatoliy
$ PMF_LANG ['ad_menu_stopwordsconfig'] = "palabras vacías";
$ PMF_LANG ['ad_config_stopword_input'] = 'Añadir palabra nueva parada ";

/ / Añade 2.5.0-beta - 06/04/2009 por Anatoliy
$ PMF_LANG ['msgSendMailDespiteEverything'] = 'No, todavía no hay respuesta adecuada (se enviará el correo);
$ PMF_LANG ['msgSendMailIfNothingIsFound'] = '¿Es la respuesta quería que figuran en los resultados anteriores? ";

/ / Añade 2.5.0-RC - 05/11/2009 por Anatoliy y Thorsten
$ PMF_LANG ['msgChooseLanguageToTranslate'] = 'Por favor, elija el idioma de la traducción;
$ PMF_LANG ['msgLangDirIsntWritable'] = 'Traducciones dir isn \' t escribible ';
$ PMF_LANG ['ad_menu_translations'] = 'traducción de la interfaz;
$ PMF_LANG ['ad_start_notactive'] = 'Esperando activación ";

/ / Añade 2.5.0-RC - 05/20/2009 por Anatoliy
$ PMF_LANG ['msgTransToolAddNewTranslation'] = 'Agregar nueva traducción';
$ PMF_LANG ['msgTransToolLanguage'] = 'Lenguaje';
$ PMF_LANG ['msgTransToolActions'] = "Acciones";
$ PMF_LANG ['msgTransToolWritable'] = 'es escribible';
$ PMF_LANG ['msgEdit'] = "Editar";
$ PMF_LANG ['msgDelete'] = 'Borrar';
$ PMF_LANG ['msgYes'] = 'sí';
$ PMF_LANG ['msgno'] = 'no';
$ PMF_LANG ['msgTransToolSureDeleteFile'] = '¿Está seguro de que este archivo de idioma debe ser eliminado? ";
$ PMF_LANG ['msgTransToolFileRemoved'] = 'Archivo de idioma eliminado con éxito';
$ PMF_LANG ['msgTransToolErrorRemovingFile'] = 'Error al eliminar el archivo de lenguaje ";
$ PMF_LANG ['msgVariable'] = 'variable';
$ PMF_LANG ['msgCancel'] = 'Cancelar';
$ PMF_LANG ['msgSave'] = 'Guardar';
$ PMF_LANG ['msgSaving3Dots'] = 'salvar ...';
$ PMF_LANG ['msgRemoving3Dots'] = 'eliminar ...';
$ PMF_LANG ['msgTransToolFileSaved'] = 'Idioma archivo guardado con éxito';
$ PMF_LANG ['msgTransToolErrorSavingFile'] = 'Error al guardar el archivo de idioma ";
$ PMF_LANG ['msgLanguage'] = 'Lenguaje';
$ PMF_LANG ['msgTransToolLanguageCharset'] = 'Idioma charset';
$ PMF_LANG ['msgTransToolLanguageDir'] = 'la dirección del idioma ";
$ PMF_LANG ['msgTransToolLanguageDesc'] = 'Descripción del lenguaje ";
$ PMF_LANG ['msgAuthor'] = 'Autor';
$ PMF_LANG ['msgTransToolAddAuthor'] = 'Añadir autor ";
$ PMF_LANG ['msgTransToolCreateTranslation'] = 'Crear traducción ";
$ PMF_LANG ['msgTransToolTransCreated'] = 'Nueva traducción creado con éxito';
$ PMF_LANG ['msgTransToolCouldntCreateTrans'] = 'No se pudo crear la nueva traducción';
$ PMF_LANG ['msgAdding3Dots'] = 'añadiendo ...';
$ PMF_LANG ['msgTransToolSendToTeam'] = 'Enviar a phpMyFAQ equipo;
$ PMF_LANG ['msgSending3Dots'] = 'el envío de ...';
$ PMF_LANG ['msgTransToolFileSent'] = 'Archivo de idioma se envió con éxito al equipo de phpMyFAQ. Muchas gracias por compartirlo.;
$ PMF_LANG ['msgTransToolErrorSendingFile'] = 'Se produjo un error al enviar el archivo de idioma ";
$ PMF_LANG ['msgTransToolPercent'] = "Porcentaje";

/ / Añade 2.5.0-RC3 - ??06/23/2009 por Anatoliy
$ LANG_CONF ['main.attachmentsPath'] = array (0 => "input", 1 => "ruta donde se guardarán los archivos adjuntos. <br /> Camino <small> relativa significa una carpeta dentro de la raíz web </ small>" );

/ / Añade 2.5.0-RC3 - ??06/24/2009 por Anatoliy
$ PMF_LANG ['msgAttachmentNotFound'] = "El archivo que está intentando descargar no se ha encontrado en este servidor";
$ PMF_LANG ['ad_sess_noentry'] = "Prohibida la entrada";

/ / Añade 2.6.0-alfa - 07/30/2009 por Aurimas Fišeras
/ / PD "Una línea de usuario" también es posible, ya que sólo hace caso omiso de sprintf argumentos adicionales
$ PMF_LANG ["plmsgUserOnline"] [0] = "% d usuarios en línea";
$ PMF_LANG ["plmsgUserOnline"] [1] = "% d usuarios en línea";

/ / Añade 2.6.0-alfa - 02/08/2009 por Anatoliy
$ LANG_CONF ['main.templateSet'] = array (0 => "seleccionar", 1 => "plantilla fija para ser utilizados");

/ / Añade 2.6.0-alfa - 08/16/2009 por Aurimas Fišeras
$ PMF_LANG ['msgTransToolRemove'] = 'Eliminar';
$ PMF_LANG ["msgTransToolLanguageNumberOfPlurals"] = "El número de formas plurales";
$ PMF_LANG ['msgTransToolLanguageOnePlural'] = 'Este lenguaje tiene una sola forma plural ";
$ PMF_LANG ['msgTransToolLanguagePluralNotSet'] = "El apoyo plural para la lengua% s está desactivado (nplurals no establecido)";

/ / Añade 2.6.0-alfa - 08/16/2009 por Fišeras Aurimas - Mensajes Plural
$ PMF_LANG ["plmsgHomeArticlesOnline"] [0] = "Hay% d FAQ en línea";
$ PMF_LANG ["plmsgHomeArticlesOnline"] [1] = "Hay% d preguntas frecuentes en línea";
$ PMF_LANG ["plmsgViews"] [0] = "% d punto de vista";
$ PMF_LANG ["plmsgViews"] [1] = "% d puntos de vista";

/ / Añade 2.6.0-alfa - 30/08/2009 por Fišeras Aurimas - Mensajes Plural
$ PMF_LANG ['plmsgGuestOnline'] [0] = '% d Invitado';
$ PMF_LANG ['plmsgGuestOnline'] [1] = '% d Invitados';
$ PMF_LANG ["plmsgRegisteredOnline '] [0] ='% d registrados;
$ PMF_LANG ["plmsgRegisteredOnline '] [1] ='% d registrados;
$ PMF_LANG ["plmsgSearchAmount"] [0] = "% d resultado de la búsqueda";
$ PMF_LANG ["plmsgSearchAmount"] [1] = "% d los resultados de búsqueda";
$ PMF_LANG ["plmsgPagesTotal"] [0] = "la página% d";
$ PMF_LANG ["plmsgPagesTotal"] [1] = "% d páginas";
$ PMF_LANG ["plmsgVotes"] [0] = "% d voto";
$ PMF_LANG ["plmsgVotes"] [1] = "Votos% d";
$ PMF_LANG ["plmsgEntries"] [0] = "% d FAQ";
$ PMF_LANG ["plmsgEntries"] [1] = "% d Preguntas frecuentes";

/ / Añade 2.6.0-alfa - 09/06/2009 por Aurimas Fišeras
$ PMF_LANG ["rightsLanguage"] ["addtranslation '] =" Añadir traducción ";
$ PMF_LANG ["rightsLanguage"] ["edittranslation '] =" Editar traducción ";
$ PMF_LANG ["rightsLanguage"] ["deltranslation '] =" Borrar traducción ";
$ PMF_LANG ["rightsLanguage"] ["approverec '] =" aprobar los registros ";

/ / Añade 2.6.0-alfa - 09/09/2009 por Anatoliy Belsky
$ LANG_CONF ["main.enableAttachmentEncryption"] = array (0 => "checkbox", 1 => "Habilitar el cifrado adjunto <small> <br> Ignorado cuando los archivos adjuntos está desactivada </> pequeño");
$ LANG_CONF ["main.defaultAttachmentEncKey"] = array (0 => "input", 1 => 'archivo adjunto cifrado de clave por defecto <small> <br> Se ignora si el cifrado está desactivado adjunto </ small> <br> <small> < color de la fuente = "rojo"> ADVERTENCIA: No cambie el cifrado de archivos, una vez establecido y habilitado !!!</ font> </ small> ');
/ / $ LANG_CONF ["main.attachmentsStorageType"] = array (0 => "seleccionar", 1 => "Tipo de almacenamiento de datos adjuntos");
/ / $ PMF_LANG ['att_storage_type'] [0] = 'Sistema de Archivos;
/ / $ PMF_LANG ['att_storage_type'] [1] = "Base de datos ';

/ / Añade 2.6.0-alfa - 09/06/2009 por Thorsten
$ PMF_LANG ['ad_menu_upgrade'] = 'Upgrade';
$ PMF_LANG ['ad_you_shouldnt_update'] = 'Usted tiene la última versión de phpMyFAQ. No es necesario actualizar.;
$ LANG_CONF ['main.useSslForLogins'] = array (0 => 'casilla', 1 => "Sólo permitir los inicios de sesión en una conexión segura (por defecto: desactivado)");
$ PMF_LANG ['msgSecureSwitch'] = "Cambiar a modo seguro para entrar!";

/ / Añade 2.6.0-alfa - 10/03/2009 por Anatoliy Belsky
$ PMF_LANG ['msgTransToolNoteFileSaving'] = 'Por favor, tenga en cuenta que no hay archivos que hemos escrito hasta que haga clic en el botón Guardar';
$ PMF_LANG ['msgTransToolPageBufferRecorded'] = '% d página buffer registrado con éxito';
$ PMF_LANG ['msgTransToolErrorRecordingPageBuffer'] = 'Error de registro de la página% d buffer';
$ PMF_LANG ['msgTransToolRecordingPageBuffer'] = '% d grabación página buffer';

/ / Añade 2.6.0-alpha - 2009-11-02 por Anatoliy Belsky
$ PMF_LANG ['ad_record_active'] = 'activo';

/ / Añade 2.6.0-alfa - 01/11/2009 por Anatoliy Belsky
$ PMF_LANG ['msgAttachmentInvalid'] = 'El archivo adjunto no es válida, por favor admin';

/ / Añade 2.6.0-alpha - 2009-11-02 por max
$ LANG_CONF ['main.numberSearchTerms'] = array (0 => "input", 1 => 'El número de términos de búsqueda lista');
$ LANG_CONF ['main.orderingPopularFaqs'] = array (0 => "seleccionar", 1 => "Clasificación de la parte superior de preguntas frecuentes");
$ PMF_LANG ['list_all_users'] = 'Lista de todos los usuarios;

$ PMF_LANG ['main.orderingPopularFaqs.visits'] = "lista de la mayoría de las entradas de visita";
$ PMF_LANG ['main.orderingPopularFaqs.voting'] = "lista más votada entradas";

/ / Añade 2.6.0-alfa - 05/11/2009 por Thorsten
$ PMF_LANG ['msgShowHelp'] = 'Por favor, las palabras separadas por comas.';

/ / Añade 2.6.0-RC - 11/30/2009 por Thorsten
$ PMF_LANG ['msgUpdateFaqDate'] = 'actualizar';
$ PMF_LANG ['msgKeepFaqDate'] = 'mantener';
$ PMF_LANG ['msgEditFaqDat'] = "Editar";
$ LANG_CONF ['main.optionalMailAddress'] = array (0 => 'casilla', 1 => 'correo electrónico como campo obligatorio (por defecto: desactivado) ");
$ LANG_CONF ['main.useAjaxSearchOnStartpage'] = array (0 => 'casilla', 1 => 'respuesta instantánea en página de inicio (por defecto: desactivado) ");

/ / Añade v2.6.99 - 11/24/2010 por Gustavo Solt
$ LANG_CONF ['search.relevance'] = array (0 => 'select', 1 => 'Ordenar por relevancia ");
$ LANG_CONF ["search.enableRelevance"] = array (0 => "checkbox", 1 => "Activar soporte relevancia (por defecto: desactivado)");
$ PMF_LANG ['searchControlCenter'] = 'Buscar';
$ PMF_LANG ['search.relevance.thema de contenido las palabras clave'] = "Pregunta - Respuesta - Palabras clave";
$ PMF_LANG ['search.relevance.thema las palabras clave de contenido'] = 'Pregunta - palabras clave - Respuesta ";
$ PMF_LANG ['search.relevance.content-thema las palabras clave'] = 'Respuestas - Pregunta - Palabras clave ";
$ PMF_LANG ['search.relevance.content-palabras-thema'] = 'Respuesta - palabras clave - Pregunta;
$ PMF_LANG ['search.relevance.keywords-content-thema'] = 'Palabras clave - Respuesta - Pregunta;
$ PMF_LANG ['search.relevance.keywords-thema de contenido'] = 'Palabras clave - Pregunta - Respuesta ";

/ / Añade v2.6.99 - 30/11/2010 por Gustavo Solt
$ LANG_CONF ['main.googleTranslationKey'] = array (0 => "input", 1 => 'Google APIs clave ");
$ LANG_CONF ["main.enableGoogleTranslation"] = array (0 => "checkbox", 1 => "Activar las traducciones de Google (por defecto: desactivado)");
$ PMF_LANG ["msgNoGoogleApiKeyFound"] = 'La clave de API de Google está vacía, por favor provea una en la sección de configuración';

/ / Añade 2.7.0-alfa - 09/13/2010 por Thorsten
$ PMF_LANG ['msgLoginUser'] = 'Login';
$ PMF_LANG ['socialNetworksControlCenter'] = 'social configuración de redes;
$ LANG_CONF ['socialnetworks.enableTwitterSupport'] = array (0 => 'casilla', 1 => 'el apoyo Twitter (por defecto: desactivado) ");
$ LANG_CONF ['socialnetworks.twitterConsumerKey'] = array (0 => "input", 1 => 'Twitter clave del consumidor');
$ LANG_CONF ['socialnetworks.twitterConsumerSecret'] = array (0 => "input", 1 => 'Twitter Consumidor Secreto');

/ / Añade 2.7.0-alfa - 14/10/2010 por Tom Zeithaml
$ LANG_CONF ['socialnetworks.twitterAccessTokenKey'] = array (0 => "input", 1 => 'Twitter token de clave de acceso');
$ LANG_CONF ['socialnetworks.twitterAccessTokenSecret'] = array (0 => "input", 1 => 'Twitter token de acceso secreto ");
$ LANG_CONF ['socialnetworks.enableFacebookSupport'] = array (0 => 'casilla', 1 => 'Facebook de apoyo (por defecto: desactivado) ");

/ / Añade 2.7.0-alfa - 21/12/2010 por Anatoliy Belsky
$ PMF_LANG ["ad_menu_attachments"] = "Archivos adjuntos";
$ PMF_LANG ["ad_menu_attachment_admin"] = "Adjunto la administración";

/ / Añade 3.0.0-alpha
$ LANG_CONF ['main.useAjaxMenu'] = array (0 => 'casilla', 1 => 'frontend Ajax potencia (por defecto: desactivado) ");