<?php
/**
 * European Portuguese language file - post-1990 Orthographic Agreement (current): pt-PT
 *
 * PHP Version 5.2
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Translation
 * @author    João Martins <jm@reit.up.pt>
 * @author    Fernando G. Monteiro <fgmont@reit.up.pt>
 * @author    Luis Costa <izhirahider@gmail.com>
 * @author... Carlos E. Gorges <carlos@linuxwaves.com>
 * @copyright 2004-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2004-06-24
 */
/**
 *                !!! IMPORTANT NOTE !!!
 * Please consider following while defining new vars:
 * - one variable definition per line !!!
 * - the perfect case is to define a scalar string value
 * - if some dynamic content is needed, use sprintf syntax
 * - arrays are allowed but not recommended
 * - no comments at the end of line after the var definition
 * - do not use '=' char in the array keys
 *   (eq. $PMF_LANG["a=b"] is not allowed)
 *
 *  Please be consistent with this format as we need it for
 *  the translation tool to work propertly
 */

$PMF_LANG['metaCharset'] = 'UTF-8';
$PMF_LANG['metaLanguage'] = 'pt';
$PMF_LANG['language'] = 'portuguese';
// ltr: left to right (e.g. English language); rtl: right to left (e.g. Arabic language)
$PMF_LANG['dir'] = 'ltr';

$PMF_LANG['nplurals'] = '2';
/**
 * This parameter is used with the function 'plural' from inc/PMF_Language/Plurals.php
 * If this parameter and function are not in sync plural form support will be broken.
 *
 * If you add a translation for a new language, correct plural form support will be missing
 * (English plural messages will be used) until you add a correct expression to the function
 * 'plural' mentioned above.
 * If you need any help, please contact phpMyFAQ team.
 */

// Navigation
$PMF_LANG['msgCategory'] = 'Categorias';
$PMF_LANG['msgShowAllCategories'] = 'Mostrar todas as categorias';
$PMF_LANG['msgSearch'] = 'Procurar';
$PMF_LANG['msgAddContent'] = 'Adicionar';
$PMF_LANG['msgQuestion'] = 'Perguntar';
$PMF_LANG['msgOpenQuestions'] = 'Questões em aberto';
$PMF_LANG['msgHelp'] = 'Ajuda';
$PMF_LANG['msgContact'] = 'Contacto';
$PMF_LANG['msgHome'] = 'Início';
$PMF_LANG['msgNews'] = ' Notícias';
$PMF_LANG['msgUserOnline'] = ' Utilizador(es) Ligado(s)';
$PMF_LANG['msgBack2Home'] = 'Voltar ao início';

// Contentpages
$PMF_LANG['msgFullCategories'] = 'Categorias com entradas';
$PMF_LANG['msgFullCategoriesIn'] = 'Categorias com entradas em ';
$PMF_LANG['msgSubCategories'] = 'Subcategorias';
$PMF_LANG['msgEntries'] = 'Entradas';
$PMF_LANG['msgEntriesIn'] = 'Entradas em ';
$PMF_LANG['msgViews'] = 'visualizações';
$PMF_LANG['msgPage'] = 'Página ';
$PMF_LANG['msgPages'] = ' Páginas ';
$PMF_LANG['msgPrevious'] = 'anterior';
$PMF_LANG['msgNext'] = 'seguinte';
$PMF_LANG['msgCategoryUp'] = 'Categoria no nível acima deste';
$PMF_LANG['msgLastUpdateArticle'] = 'Atualização mais recente: ';
$PMF_LANG['msgAuthor'] = 'Autor';
$PMF_LANG['msgPrinterFriendly'] = 'Versão para impressão';
$PMF_LANG['msgPrintArticle'] = 'Imprimir';
$PMF_LANG['msgMakeXMLExport'] = 'Exportar para um ficheiro em XML';
$PMF_LANG['msgAverageVote'] = 'Avaliação média:';
$PMF_LANG['msgVoteUseability'] = 'Avaliação deste registo';
$PMF_LANG['msgVoteFrom'] = ' de ';
$PMF_LANG['msgVoteBad'] = 'Completamente inútil';
$PMF_LANG['msgVoteGood'] = 'Muito útil';
$PMF_LANG['msgVotings'] = 'Avaliações ';
$PMF_LANG['msgVoteSubmit'] = 'Avaliar';
$PMF_LANG['msgVoteThanks'] = 'Obrigado pela sua avaliação.';
$PMF_LANG['msgYouCan'] = 'Pode ';
$PMF_LANG['msgWriteComment'] = 'Comentar este artigo';
$PMF_LANG['msgShowCategory'] = 'Visão geral do conteúdo: ';
$PMF_LANG['msgCommentBy'] = 'Comentário de ';
$PMF_LANG['msgCommentHeader'] = 'Comentar este artigo';
$PMF_LANG['msgYourComment'] = 'O seu comentário:';
$PMF_LANG['msgCommentThanks'] = 'Obrigado pelo seu comentário.';
$PMF_LANG['msgSeeXMLFile'] = 'abrir o Ficheiro-XML';
$PMF_LANG['msgSend2Friend'] = 'Enviar para um Amigo';
$PMF_LANG['msgS2FName'] = 'Nome:';
$PMF_LANG['msgS2FEMail'] = '<em>E-mail</em>:';
$PMF_LANG['msgS2FFriends'] = 'Amigos:';
$PMF_LANG['msgS2FEMails'] = '. <em>E-mail</em>:';
$PMF_LANG['msgS2FText'] = 'Texto a enviar:';
$PMF_LANG['msgS2FText2'] = 'O artigo encontra-se no endereço:';
$PMF_LANG['msgS2FMessage'] = 'Mensagem adicional:';
$PMF_LANG['msgS2FButton'] = 'enviar o \'e-mail\'';
$PMF_LANG['msgS2FThx'] = 'Obrigado pela sua recomendação.';
$PMF_LANG['msgS2FMailSubject'] = 'Recomendado(a) por ';

// Search
$PMF_LANG['msgSearchWord'] = 'Palavra(s)-Chave';
$PMF_LANG['msgSearchFind'] = 'Resultado da pesquisa de ';
$PMF_LANG['msgSearchAmount'] = ' resultado da pesquisa';
$PMF_LANG['msgSearchAmounts'] = ' resultados da pesquisa';
$PMF_LANG['msgSearchCategory'] = 'Categoria: ';
$PMF_LANG['msgSearchContent'] = 'Conteúdo: ';

// new Content
$PMF_LANG['msgNewContentHeader'] = 'Proposta para as FAQ';
$PMF_LANG['msgNewContentAddon'] = 'A sua proposta não será publicada imediatamente mas será avaliada pelos editores. Os campos obrigatórios são: <strong>Nome</strong>, <strong><em>E-mail</em></strong>, <strong>Categoria</strong>, <strong>Cabeçalho</strong> (tema) e <strong>Conteúdo</strong>. As palavras-chave têm que ser separadas apenas por vírgulas.';
$PMF_LANG['msgNewContentName'] = 'Nome:';
$PMF_LANG['msgNewContentMail'] = '<em>E-mail</em>:';
$PMF_LANG['msgNewContentCategory'] = 'Categoria:';
$PMF_LANG['msgNewContentTheme'] = 'Cabeçalho <small>(tema)</small>:';
$PMF_LANG['msgNewContentArticle'] = 'Conteúdo <small>(resposta)</small>:';
$PMF_LANG['msgNewContentKeywords'] = 'Palavras-chave:';
$PMF_LANG['msgNewContentLink'] = '<em>Link</em> para este registo:';
$PMF_LANG['msgNewContentSubmit'] = 'Inserir';
$PMF_LANG['msgInfo'] = 'Informações extra: ';
$PMF_LANG['msgNewContentThanks'] = 'Obrigado pela sua contribuição.';
$PMF_LANG['msgNoQuestionsAvailable'] = 'Neste momento não existem questões em aberto <small>(i.e., em lista de espera)</small>.';

// ask Question
$PMF_LANG['msgNewQuestion'] = 'Pode adicionar novas questões usando esta página.';
$PMF_LANG['msgAskCategory'] = 'Questão sobre a categoria:';
$PMF_LANG['msgAskYourQuestion'] = 'A sua questão:';
$PMF_LANG['msgAskThx4Mail'] = 'Obrigado pela sua questão / <em>e-mail</em>.';
$PMF_LANG['msgDate_User'] = 'Data / Utilizador';
$PMF_LANG['msgQuestion2'] = 'Questão';
$PMF_LANG['msg2answer'] = 'Resposta';
$PMF_LANG['msgQuestionText'] = 'Nesta página pode ver algumas questões colocadas por outros utilizadores.<br />Pode contribuir com respostas (serão analisadas para inclusão nas FAQ).';

// Help
$PMF_LANG['msgHelpText'] = '<p>As FAQ (<em><strong>F</strong>requently <strong>A</strong>sked <strong>Q</strong>uestions</em> em inglês) têm uma estrutura bastante simples.<br />Pode procurar artigos usando a lista de <strong><a href="?action=show" title="">categorias</a></strong> ou introduzindo palavras-chave no <strong><a href="?action=search" title="">motor de pesquisa das FAQ</a></strong>.</p>';

// Contact
$PMF_LANG['msgContactEMail'] = '<em>E-mail</em> para o <em>webmaster</em>:';
$PMF_LANG['msgMessage'] = 'Mensagem:';

// Startseite
$PMF_LANG['msgNews'] = ' Notícias';
$PMF_LANG['msgTopTen'] = 'TOP 10';
$PMF_LANG['msgHomeThereAre'] = 'Existe(m) ';
$PMF_LANG['msgHomeArticlesOnline'] = ' artigo(s) <em>on-line</em>';
$PMF_LANG['msgNoNews'] = 'Não existem novas notícias.';
$PMF_LANG['msgLatestArticles'] = 'Os 5 registos mais recentes';

// E-Mailbenachrichtigung
$PMF_LANG['msgMailThanks'] = 'Obrigado pela sua proposta para as FAQ.';
$PMF_LANG['msgMailCheck'] = 'Existe um novo registo nas FAQ: consultar a secção de Administração.';
$PMF_LANG['msgMailContact'] = 'A sua mensagem foi enviada para o Administrador.';

// Fehlermeldungen
$PMF_LANG['err_noDatabase'] = 'Sem ligação à base de dados.';
$PMF_LANG['err_noHeaders'] = 'Sem categorias.';
$PMF_LANG['err_noArticles'] = '<p>Sem artigos disponíveis.</p>';
$PMF_LANG['err_badID'] = '<p>ID Incorreto.</p>';
$PMF_LANG['err_noTopTen'] = '<p>O TOP 10 ainda não se encontra disponível.</p>';
$PMF_LANG['err_nothingFound'] = '<p>Sem artigos.</p>';
$PMF_LANG['err_SaveEntries'] = 'Campos obrigatórios: <strong>Nome</strong>, <strong><em>E-mail</em></strong>, <strong>Categoria</strong>, <strong>Questão</strong>, a sua <strong>Entrada</strong> e, quando exigido, o código <strong><a href="http://en.wikipedia.org/wiki/Captcha" title="Informação sobre Captcha na Wikipedia - versão em inglês" target="_blank"><em>Captcha</em></a></strong>.<br /> <br /><a href="javascript:history.back();">Voltar atrás</a><br /> <br /> ';
$PMF_LANG['err_SaveComment'] = 'Campos obrigatórios: <strong>Nome</strong>, <strong><em>E-mail</em></strong>, <strong>Comentário</strong> e, quando exigido, o código <strong><a href="http://en.wikipedia.org/wiki/Captcha" title="Informação sobre Captcha na Wikipedia - versão em inglês" target="_blank"><em>Captcha</em></a></strong>.<br /> <br /><a href="javascript:history.back();">Voltar atrás</a><br /> <br /> ';
$PMF_LANG['err_VoteTooMuch'] = 'Avaliações em duplicado não são contabilizadas.';
$PMF_LANG['err_noVote'] = '<strong>Não efetuou a avaliação.</strong>';
$PMF_LANG['err_noMailAdress'] = '<em>E-mail</em> incorreto.';
$PMF_LANG['err_sendMail'] = 'Campos obrigatórios: <strong>Nome</strong>, <strong><em>E-mail</em></strong>, <strong>Questão</strong> e, quando exigido, o código <strong><a href="http://en.wikipedia.org/wiki/Captcha" title="Informação sobre Captcha na Wikipedia - versão em inglês" target="_blank"><em>Captcha</em></a></strong>.';

// Hilfe zur Suche
$PMF_LANG['help_search'] = '<p><strong>Pesquisa de artigos:</strong><br />Usando uma expressão do tipo <strong style="color: red;">palavra1 palavra2</strong> pode obter resultados por relevância descendente para dois ou mais critérios de pesquisa.</p><p><strong>Nota:</strong> A expressão a pesquisar tem que ter pelo menos 4 carateres, caso contrário será ignorada.</p>';

// Menï¿½
$PMF_LANG['ad'] = '<strong>Secção de Administração</strong>';
$PMF_LANG['ad_menu_user_administration'] = 'Administrar Utilizadores';
$PMF_LANG['ad_menu_entry_aprove'] = 'Aprovar Artigos';
$PMF_LANG['ad_menu_entry_edit'] = 'Editar Artigos';
$PMF_LANG['ad_menu_categ_add'] = 'Adicionar Categorias';
$PMF_LANG['ad_menu_categ_edit'] = 'Administração de Categorias';
$PMF_LANG['ad_menu_news_add'] = 'Adicionar Notícias';
$PMF_LANG['ad_menu_news_edit'] = 'Editar Notícias';
$PMF_LANG['ad_menu_open'] = 'Editar Questões em Aberto';
$PMF_LANG['ad_menu_stat'] = 'Estatísticas de Avaliação';
$PMF_LANG['ad_menu_cookie'] = 'Editar <em>cookies</em>';
$PMF_LANG['ad_menu_session'] = 'Ver Sessões';
$PMF_LANG['ad_menu_adminlog'] = 'Ver o <em>Adminlog</em>';
$PMF_LANG['ad_menu_passwd'] = 'Alterar a sua <em>Password</em>';
$PMF_LANG['ad_menu_logout'] = 'Sair';
$PMF_LANG['ad_menu_startpage'] = 'Página Inicial';

// Nachrichten
$PMF_LANG['ad_msg_identify'] = 'Identifique-se p.f.';
$PMF_LANG['ad_msg_passmatch'] = 'A nova <em>password</em> tem que ser <strong>igual nos dois campos</strong>.';
$PMF_LANG['ad_msg_savedsuc_1'] = 'O perfil de ';
$PMF_LANG['ad_msg_savedsuc_2'] = ' foi gravado.';
$PMF_LANG['ad_msg_mysqlerr'] = 'Devido a um <strong>erro na base de dados</strong> o perfil não foi gravado.';
$PMF_LANG['ad_msg_noauth'] = 'Não possui autorização para realizar esta operação.';

// Allgemein
$PMF_LANG['ad_gen_page'] = 'Página ';
$PMF_LANG['ad_gen_of'] = ' de ';
$PMF_LANG['ad_gen_lastpage'] = 'Página anterior';
$PMF_LANG['ad_gen_nextpage'] = 'Página seguinte';
$PMF_LANG['ad_gen_save'] = 'Gravar';
$PMF_LANG['ad_gen_reset'] = 'Limpar';
$PMF_LANG['ad_gen_yes'] = 'Sim';
$PMF_LANG['ad_gen_no'] = 'Não';
$PMF_LANG['ad_gen_top'] = 'Topo';
$PMF_LANG['ad_gen_ncf'] = 'Não foi encontrada nenhuma categoria.';
$PMF_LANG['ad_gen_delete'] = 'Apagar';

// Benutzerverwaltung
$PMF_LANG['ad_user'] = 'Administração de Utilizadores';
$PMF_LANG['ad_user_username'] = 'Utilizadores Registados';
$PMF_LANG['ad_user_rights'] = 'Permissões';
$PMF_LANG['ad_user_edit'] = 'editar';
$PMF_LANG['ad_user_delete'] = 'apagar';
$PMF_LANG['ad_user_add'] = 'Adicionar Utilizador';
$PMF_LANG['ad_user_profou'] = 'Perfil do utilizador ';
$PMF_LANG['ad_user_name'] = 'Nome';
$PMF_LANG['ad_user_password'] = '<em>Password<em>';
$PMF_LANG['ad_user_confirm'] = 'Confirmar';
$PMF_LANG['ad_user_rights'] = 'Permissões';
$PMF_LANG['ad_user_del_1'] = 'O Utilizador ';
$PMF_LANG['ad_user_del_2'] = ' deve ser apagado?';
$PMF_LANG['ad_user_del_3'] = 'Tem a certeza?';
$PMF_LANG['ad_user_deleted'] = 'O Utilizador foi apagado.';
$PMF_LANG['ad_user_checkall'] = 'Selecionar Tudo';

// Beitragsverwaltung
$PMF_LANG['ad_entry_aor'] = 'Administração de Registos';
$PMF_LANG['ad_entry_id'] = 'ID ';
$PMF_LANG['ad_entry_topic'] = 'Tópico';
$PMF_LANG['ad_entry_action'] = 'Ação';
$PMF_LANG['ad_entry_edit_1'] = 'Editar Registo';
$PMF_LANG['ad_entry_edit_2'] = '';
$PMF_LANG['ad_entry_theme'] = 'Tema:';
$PMF_LANG['ad_entry_content'] = 'Conteúdo:';
$PMF_LANG['ad_entry_keywords'] = 'Palavras-Chave:';
$PMF_LANG['ad_entry_author'] = 'Autor:';
$PMF_LANG['ad_entry_category'] = 'Categoria:';
$PMF_LANG['ad_entry_active'] = 'Ativo?';
$PMF_LANG['ad_entry_date'] = 'Data:';
$PMF_LANG['ad_entry_changed'] = 'Alterado?';
$PMF_LANG['ad_entry_changelog'] = '<em>Changelog</em>';
$PMF_LANG['ad_entry_commentby'] = 'Comentário de ';
$PMF_LANG['ad_entry_comment'] = 'Comentários:';
$PMF_LANG['ad_entry_save'] = 'Gravar';
$PMF_LANG['ad_entry_delete'] = 'Apagar';
$PMF_LANG['ad_entry_delcom_1'] = 'Tem a certeza que o comentário do utilizador ';
$PMF_LANG['ad_entry_delcom_2'] = ' deve ser apagado?';
$PMF_LANG['ad_entry_commentdelsuc'] = 'O Comentário foi <strong>apagado</strong>.';
$PMF_LANG['ad_entry_back'] = 'Voltar atrás';
$PMF_LANG['ad_entry_commentdelfail'] = 'O Comentário <strong>não</strong> foi apagado.';
$PMF_LANG['ad_entry_savedsuc'] = 'As alterações foram <strong>gravadas</strong>.';
$PMF_LANG['ad_entry_savedfail'] = 'Ocorreu um <strong>erro na base de dados</strong>.';
$PMF_LANG['ad_entry_del_1'] = 'Tem a certeza que o tópico ';
$PMF_LANG['ad_entry_del_2'] = ' de ';
$PMF_LANG['ad_entry_del_3'] = ' deve ser apagado?';
$PMF_LANG['ad_entry_delsuc'] = '<strong>Apagado(a)</strong>.';
$PMF_LANG['ad_entry_delfail'] = '<strong>Não foi</strong> apagado(a).';
$PMF_LANG['ad_entry_back'] = 'Voltar atrás';


// Newsverwaltung
$PMF_LANG['ad_news_header'] = 'Cabeçalho da Notícia:';
$PMF_LANG['ad_news_text'] = 'Texto da Notícia';
$PMF_LANG['ad_news_link_url'] = '<em>Link</em> :';
$PMF_LANG['ad_news_link_title'] = 'Título do <em>link</em>:';
$PMF_LANG['ad_news_link_target'] = 'Destino do <em>link</em>:';
$PMF_LANG['ad_news_link_window'] = ' O <em>link</em> será aberto numa nova janela';
$PMF_LANG['ad_news_link_faq'] = ' <em>Link</em> dentro das FAQ';
$PMF_LANG['ad_news_add'] = 'Adicionar Notícia';
$PMF_LANG['ad_news_id'] = '#';
$PMF_LANG['ad_news_headline'] = 'Cabeçalho';
$PMF_LANG['ad_news_date'] = 'Data';
$PMF_LANG['ad_news_action'] = 'Ação';
$PMF_LANG['ad_news_update'] = 'Atualizar';
$PMF_LANG['ad_news_delete'] = 'Apagar';
$PMF_LANG['ad_news_nodata'] = 'Não foi encontrada informação na base de dados.';
$PMF_LANG['ad_news_updatesuc'] = 'As Notícias foram atualizadas.';
$PMF_LANG['ad_news_del'] = 'Tem a certeza que deseja apagar esta notícia?';
$PMF_LANG['ad_news_yesdelete'] = 'Sim, apagar.';
$PMF_LANG['ad_news_nodelete'] = 'Não apagar';
$PMF_LANG['ad_news_delsuc'] = 'Notícia <strong>apagada</strong>.';
$PMF_LANG['ad_news_updatenews'] = 'A Notícia foi atualizada.';

// Kategorieverwaltung
$PMF_LANG['ad_categ_new'] = 'Adicionar Categoria';
$PMF_LANG['ad_categ_catnum'] = 'Categoria Número:';
$PMF_LANG['ad_categ_subcatnum'] = 'Subcategoria Número:';
$PMF_LANG['ad_categ_nya'] = '<em> ainda não se encontra disponível.</em>';
$PMF_LANG['ad_categ_titel'] = 'Título da Categoria';
$PMF_LANG['ad_categ_add'] = 'Adicionar Categoria';
$PMF_LANG['ad_categ_existing'] = 'Categorias Existentes';
$PMF_LANG['ad_categ_id'] = '#';
$PMF_LANG['ad_categ_categ'] = 'Categoria';
$PMF_LANG['ad_categ_subcateg'] = 'Subcategoria';
$PMF_LANG['ad_categ_titel'] = 'Título da Categoria';
$PMF_LANG['ad_categ_action'] = 'Ação';
$PMF_LANG['ad_categ_update'] = 'atualizar';
$PMF_LANG['ad_categ_delete'] = 'Apagar';
$PMF_LANG['ad_categ_updatecateg'] = 'Atualizar Categorias';
$PMF_LANG['ad_categ_nodata'] = 'Não foi encontrada informação na base de dados.';
$PMF_LANG['ad_categ_remark'] = '<em>Atenção</em> : Quando uma categoria é eliminada os seus artigos <em>deixam</em> de ser visíveis.<br />É necessário atribuir-lhes previamente <em>outra</em> categoria para os <em>manter</em> (caso contrário deve apagá-los).';
$PMF_LANG['ad_categ_edit_1'] = 'Editar';
$PMF_LANG['ad_categ_edit_2'] = 'Categoria';
$PMF_LANG['ad_categ_add'] = 'Adicionar Categoria';
$PMF_LANG['ad_categ_added'] = 'A Categoria foi adicionada.';
$PMF_LANG['ad_categ_updated'] = 'A Categoria foi atualizada.';
$PMF_LANG['ad_categ_del_yes'] = 'Sim, apagar.';
$PMF_LANG['ad_categ_del_no'] = 'Não apagar.';
$PMF_LANG['ad_categ_deletesure'] = 'Tem a certeza que pretende apagar esta categoria?';
$PMF_LANG['ad_categ_deleted'] = 'A Categoria foi <strong>apagada</strong>.';

// Cookies
$PMF_LANG['ad_cookie_cookiesuc'] = 'O <em>cookie</em> foi <strong>adicionado</strong>.';
$PMF_LANG['ad_cookie_already'] = 'Já existe um <em>cookie</em>. Opções disponíveis:';
$PMF_LANG['ad_cookie_again'] = 'Alterar o <em>cookie</em> existente';
$PMF_LANG['ad_cookie_delete'] = 'Apagar o <em>cookie</em>';
$PMF_LANG['ad_cookie_no'] = 'Não existe nenhum <em>cookie</em> gravado. Usando <em>cookies</em> pode evitar efetuar o <em>login</em> manualmente de forma repetitiva. Opções disponíveis:';
$PMF_LANG['ad_cookie_set'] = 'Adicionar <em>cookie</em>';
$PMF_LANG['ad_cookie_deleted'] = 'O <em>cookie</em> foi <strong>apagado</strong>.';

// Adminlog
$PMF_LANG['ad_adminlog'] = 'Registo de Administração <small>(<em>AdminLog</em> )</small>';

// Passwd
$PMF_LANG['ad_passwd_cop'] = 'Alterar a sua <em>password</em>';
$PMF_LANG['ad_passwd_old'] = '<em>Password</em> Antiga :';
$PMF_LANG['ad_passwd_new'] = 'Nova <em>password</em> :';
$PMF_LANG['ad_passwd_con'] = 'Confirmar:';
$PMF_LANG['ad_passwd_change'] = 'Alterar a \'password\'';
$PMF_LANG['ad_passwd_suc'] = 'A <em>Password</em> foi <strong>alterada</strong>.';
$PMF_LANG['ad_passwd_remark'] = '<strong>Atenção:</strong><br />Tem que adicionar novamente o <em>cookie</em>.';
$PMF_LANG['ad_passwd_fail'] = 'A <em>password</em> antiga <strong>tem que</strong> ser introduzida corretamente e a nova tem que ser <strong>igual</strong> nos <strong>dois</strong> campos.';

// Adduser
$PMF_LANG['ad_adus_adduser'] = 'Adicionar Utilizador';
$PMF_LANG['ad_adus_name'] = 'Nome <small>(<em>login</em>)</small>:';
$PMF_LANG['ad_adus_password'] = '<em>Password</em>:';
$PMF_LANG['ad_adus_add'] = 'Adicionar utilizador';
$PMF_LANG['ad_adus_suc'] = 'O utilizador foi <strong>adicionado</strong>.';
$PMF_LANG['ad_adus_edit'] = 'Editar perfil';
$PMF_LANG['ad_adus_dberr'] = '<strong>Erro na base de dados</strong>.';
$PMF_LANG['ad_adus_exerr'] = 'O utilizador <strong>já existe</strong>.';

// Sessions
$PMF_LANG['ad_sess_id'] = 'ID ';
$PMF_LANG['ad_sess_sid'] = 'ID da Sessão';
$PMF_LANG['ad_sess_ip'] = 'Endereço IP';
$PMF_LANG['ad_sess_time'] = 'Hora';
$PMF_LANG['ad_sess_pageviews'] = 'Visualizações';
$PMF_LANG['ad_sess_search'] = 'Pesquisa';
$PMF_LANG['ad_sess_sfs'] = 'Pesquisar Sessões';
$PMF_LANG['ad_sess_s_ip'] = ' IP:';
$PMF_LANG['ad_sess_s_minct'] = 'Ações min.:';
$PMF_LANG['ad_sess_s_date'] = 'Data';
$PMF_LANG['ad_sess_s_after'] = ' depois de ';
$PMF_LANG['ad_sess_s_before'] = ' antes de ';
$PMF_LANG['ad_sess_s_search'] = 'Pesquisa';
$PMF_LANG['ad_sess_session'] = 'Sessão';
$PMF_LANG['ad_sess_r'] = 'Resultados da pesquisa de ';
$PMF_LANG['ad_sess_referer'] = '<em>Referer</em>:';
$PMF_LANG['ad_sess_browser'] = '<em>Browser</em>:';
$PMF_LANG['ad_sess_ai_rubrik'] = 'Categoria:';
$PMF_LANG['ad_sess_ai_artikel'] = 'Artigo:';
$PMF_LANG['ad_sess_ai_sb'] = 'Termo(s) na(s) pesquisa(s):';
$PMF_LANG['ad_sess_ai_sid'] = 'ID da Sessão:';
$PMF_LANG['ad_sess_back'] = 'Voltar atrás';

// Statistik
$PMF_LANG['ad_rs'] = 'Estatísticas de Avaliação';
$PMF_LANG['ad_rs_rating_1'] = 'A Avaliação de ';
$PMF_LANG['ad_rs_rating_2'] = 'utilizador(es) mostra:';
$PMF_LANG['ad_rs_red'] = 'Vermelho';
$PMF_LANG['ad_rs_green'] = 'Verde';
$PMF_LANG['ad_rs_altt'] = ' : média inferior a 20% ';
$PMF_LANG['ad_rs_ahtf'] = ' : média superior a 40% ';
$PMF_LANG['ad_rs_no'] = 'Sem Avaliação';

// Auth
$PMF_LANG['ad_auth_insert'] = 'Introduza o seu <em>username</em> e a sua <em>password</em>.';
$PMF_LANG['ad_auth_user'] = '<em>Username</em>:';
$PMF_LANG['ad_auth_passwd'] = '<em>Password</em>:';
$PMF_LANG['ad_auth_ok'] = 'OK ';
$PMF_LANG['ad_auth_reset'] = 'Limpar';
$PMF_LANG['ad_auth_fail'] = 'Utilizador e/ou <em>password</em> incorreto(s).';
$PMF_LANG['ad_auth_sess'] = 'O ID da sessão foi enviado.';

// Added v0.8 - 24.05.2001 - Bastian - Admin
$PMF_LANG['ad_config_edit'] = 'Configuração do phpMyFAQ';
$PMF_LANG['ad_config_save'] = 'Gravar a configuração';
$PMF_LANG['ad_config_reset'] = 'Limpar / Cancelar';
$PMF_LANG['ad_config_saved'] = 'A configuração foi <strong>gravada</strong>.';
$PMF_LANG['ad_menu_editconfig'] = 'Editar a configuração';
$PMF_LANG['ad_att_none'] = 'Não existem anexos disponíveis';
$PMF_LANG['ad_att_att'] = 'Anexos:';
$PMF_LANG['ad_att_add'] = 'Anexar ficheiro';
$PMF_LANG['ad_entryins_suc'] = 'O artigo foi <strong>gravado</strong>.';
$PMF_LANG['ad_entryins_fail'] = 'Ocorreu um erro.';
$PMF_LANG['ad_att_del'] = 'Apagar';
$PMF_LANG['ad_att_nope'] = '<small><strong>Nota</strong>: só é possível inserir anexos em registos <strong>já gravados</strong> e <strong>apenas</strong> no modo de edição.</small>';
$PMF_LANG['ad_att_delsuc'] = 'O anexo foi <strong>apagado</strong>.';
$PMF_LANG['ad_att_delfail'] = 'Ocorreu um erro ao apagar o anexo.';
$PMF_LANG['ad_entry_add'] = 'Criar Artigo';

// Added v0.85 - 08.06.2001 - Bastian - Admin
$PMF_LANG['ad_csv_make'] = 'Um <em>backup</em> é uma imagem completa do conteúdo da base de dados, devendo ser efetuado pelo menos uma vez por mês. O formato do <em>backup</em> corresponde a uma <em>SQL transaction file</em> , pelo que pode ser usado por aplicações externas gráficas - tal como o <em>phpMyAdmin</em> - ou através da linha de comandos - via cliente <em>SQL</em> .';
$PMF_LANG['ad_csv_link'] = '<em>Download</em> do <em>backup</em>';
$PMF_LANG['ad_csv_head'] = 'Criar';
$PMF_LANG['ad_att_addto'] = 'Adicionar um anexo.';
$PMF_LANG['ad_att_addto_2'] = '';
$PMF_LANG['ad_att_att'] = 'Ficheiro:';
$PMF_LANG['ad_att_butt'] = 'OK ';
$PMF_LANG['ad_att_suc'] = 'O ficheiro foi <strong>anexado</strong>.';
$PMF_LANG['ad_att_fail'] = 'Ocorreu um erro ao anexar o ficheiro.';
$PMF_LANG['ad_att_close'] = 'Fechar esta janela.';

// Added v0.85 - 08.07.2001 - Bastian - Admin
$PMF_LANG['ad_csv_restore'] = 'Através deste formulário pode restaurar o conteúdo da base de dados usando um <em>backup</em> efetuado previamente pelo phpMyFAQ.<br />Atenção: os dados existentes <strong>serão apagados</strong>.';
$PMF_LANG['ad_csv_file'] = 'Ficheiro';
$PMF_LANG['ad_csv_ok'] = 'OK ';
$PMF_LANG['ad_csv_linklog'] = '<em>Backup</em> de LOGs';
$PMF_LANG['ad_csv_linkdat'] = '<em>Backup</em> de dados';
$PMF_LANG['ad_csv_head2'] = 'Restaurar';
$PMF_LANG['ad_csv_no'] = 'Aparentemente, não se trata de um <em>backup</em> do <em>phpMyFAQ</em> .';
$PMF_LANG['ad_csv_prepare'] = 'A preparar as consultas à Base de Dados…';
$PMF_LANG['ad_csv_process'] = 'A interrogar a Base de Dados…';
$PMF_LANG['ad_csv_of'] = ' de ';
$PMF_LANG['ad_csv_suc'] = ' com sucesso.';
$PMF_LANG['ad_csv_backup'] = '<em>Backup</em>';
$PMF_LANG['ad_csv_rest'] = 'Restaurar um <em>backup</em>';

// Added v0.8 - 25.05.2001 - Bastian - Admin
$PMF_LANG['ad_menu_backup'] = '<em>Backup</em>';
$PMF_LANG['ad_logout'] = 'Sessão terminada sem problemas.';
$PMF_LANG['ad_news_add'] = 'Adicionar Notícia';
$PMF_LANG['ad_news_edit'] = 'Editar Notícias';
$PMF_LANG['ad_cookie'] = '<em>Cookies</em>';
$PMF_LANG['ad_sess_head'] = 'Consultar Sessões';

// Added v1.1 - 06.01.2002 - Bastian
$PMF_LANG['ad_menu_categ_edit'] = 'Categorias';
$PMF_LANG['ad_menu_stat'] = 'Estatísticas de Avaliação';
$PMF_LANG['ad_kateg_add'] = 'Adicionar Categoria de Topo';
$PMF_LANG['ad_kateg_rename'] = 'Renomear';
$PMF_LANG['ad_adminlog_date'] = 'Data e Hora';
$PMF_LANG['ad_adminlog_user'] = 'Utilizador';
$PMF_LANG['ad_adminlog_ip'] = 'Endereço IP';

$PMF_LANG['ad_stat_sess'] = 'Sessões';
$PMF_LANG['ad_stat_days'] = 'N.º de Dias registados';
$PMF_LANG['ad_stat_vis'] = 'Sessões <small>(visitas)</small>';
$PMF_LANG['ad_stat_vpd'] = 'Visitas por Dia';
$PMF_LANG['ad_stat_fien'] = 'Registo Inicial';
$PMF_LANG['ad_stat_laen'] = 'Registo mais Recente';
$PMF_LANG['ad_stat_browse'] = 'Consultar as Sessões de';
$PMF_LANG['ad_stat_ok'] = 'OK ';

$PMF_LANG['ad_sess_time'] = 'Data e Hora';
$PMF_LANG['ad_sess_sid'] = '#';
$PMF_LANG['ad_sess_ip'] = 'Endereço IP';

$PMF_LANG['ad_ques_take'] = 'Aceitar a questão e Editar';
$PMF_LANG['no_cats'] = 'Não foi encontrada nenhuma Categoria.';

// Added v1.1 - 17.01.2002 - Bastian
$PMF_LANG['ad_log_lger'] = '<em>Password</em> e/ou Utilizador incorreto(s).';
$PMF_LANG['ad_log_sess'] = 'A sessão expirou.';
$PMF_LANG['ad_log_edit'] = '"Editar Utilizador" - Formulário do utilizador: ';
$PMF_LANG['ad_log_crea'] = '"Novo Artigo" - Formulário.';
$PMF_LANG['ad_log_crsa'] = 'Foi criada uma nova entrada.';
$PMF_LANG['ad_log_ussa'] = 'Atualizar os dados do utilizador: ';
$PMF_LANG['ad_log_usde'] = 'Foi <strong>apagado</strong> o utilizador: ';
$PMF_LANG['ad_log_beed'] = 'Formulário de edição do utilizador: ';
$PMF_LANG['ad_log_bede'] = 'Foi <strong>apagada</strong> a entrada: ';

$PMF_LANG['ad_start_visits'] = 'Visitas';
$PMF_LANG['ad_start_articles'] = 'Artigos';
$PMF_LANG['ad_start_comments'] = 'Comentários';


// Added v1.1 - 30.01.2002 - Bastian
$PMF_LANG['ad_categ_paste'] = 'Colar';
$PMF_LANG['ad_categ_cut'] = 'Cortar';
$PMF_LANG['ad_categ_copy'] = 'Copiar';
$PMF_LANG['ad_categ_process'] = 'A processar as categorias…';

// Added v1.1.4 - 07.05.2002 - Thorsten
$PMF_LANG['err_NotAuth'] = '<strong>Não possui autorização</strong> para realizar esta operação.';

// Added v1.2.3 - 29.11.2002 - Thorsten
$PMF_LANG['msgPreviusPage'] = 'Página anterior ';
$PMF_LANG['msgNextPage'] = ' Página seguinte ';
$PMF_LANG['msgPageDoublePoint'] = 'Página: ';
$PMF_LANG['msgMainCategory'] = 'Categoria principal';

// Added v1.2.4 - 30.01.2003 - Thorsten
$PMF_LANG['ad_passwdsuc'] = 'A sua <em>password</em> foi alterada.';

// Added v1.3.0 - 04.03.2003 - Thorsten
$PMF_LANG['msgPDF'] = 'Ver num ficheiro-PDF';
$PMF_LANG['ad_xml_head'] = '<em>Backup<em> em XML';
$PMF_LANG['ad_xml_hint'] = 'Gravar todos os registos das suas FAQ num ficheiro-XML.';
$PMF_LANG['ad_xml_gen'] = ' XML';
$PMF_LANG['ad_entry_locale'] = 'Idioma';
$PMF_LANG['msgLangaugeSubmit'] = 'Escolha o idioma:';

// Added v1.3.1 - 29.04.2003 - Thorsten
$PMF_LANG['ad_entry_preview'] = 'Pré-visualização';
$PMF_LANG['ad_attach_1'] = 'Escolha um diretório para anexos na secção de configuração <small>(antes de realizar esta operação)</small>.';
$PMF_LANG['ad_attach_2'] = 'Escolha um <em>link</em> para anexos na secção de configuração <small>(antes de realizar esta operação)</small>.';
$PMF_LANG['ad_attach_3'] = 'O ficheiro <em>attachment.php</em> não pode ser acedido sem autenticação prévia.';
$PMF_LANG['ad_attach_4'] = 'O anexo tem que possuir menos de %s Bytes.';
$PMF_LANG['ad_menu_export'] = 'Exportar as FAQ';
$PMF_LANG['ad_export_1'] = 'Foi criado um ficheiro de <em>feeds</em> RSS em ';
$PMF_LANG['ad_export_2'] = '.';
$PMF_LANG['ad_export_file'] = 'Erro: foi impossível escrever (n)o ficheiro.';
$PMF_LANG['ad_export_news'] = 'Notícias via <em>feed</em> RSS';
$PMF_LANG['ad_export_topten'] = '<em>Top 10 RSS-Feed</em>';
$PMF_LANG['ad_export_latest'] = 'Os 5 registos mais recentes de <em>feeds</em> RSS';
$PMF_LANG['ad_export_pdf'] = 'Exportar todos os registos no formato PDF';
$PMF_LANG['ad_export_generate'] = 'Criar <em>feed</em> RSS';

$PMF_LANG['rightsLanguage']['adduser'] = 'adicionar utilizador';
$PMF_LANG['rightsLanguage']['edituser'] = 'editar utilizador';
$PMF_LANG['rightsLanguage']['deluser'] = 'apagar utilizador';
$PMF_LANG['rightsLanguage']['addbt'] = 'adicionar registo';
$PMF_LANG['rightsLanguage']['editbt'] = 'editar registo';
$PMF_LANG['rightsLanguage']['delbt'] = 'apagar registo';
$PMF_LANG['rightsLanguage']['viewlog'] = 'ver <em>log</em>';
$PMF_LANG['rightsLanguage']['adminlog'] = 'ver o <em>admin log</em>';
$PMF_LANG['rightsLanguage']['delcomment'] = 'apagar comentário';
$PMF_LANG['rightsLanguage']['addnews'] = 'adicionar notícia';
$PMF_LANG['rightsLanguage']['editnews'] = 'editar notícia';
$PMF_LANG['rightsLanguage']['delnews'] = 'apagar notícia';
$PMF_LANG['rightsLanguage']['addcateg'] = 'adicionar categoria';
$PMF_LANG['rightsLanguage']['editcateg'] = 'editar categoria';
$PMF_LANG['rightsLanguage']['delcateg'] = 'apagar categoria';
$PMF_LANG['rightsLanguage']['passwd'] = 'alterar a <em>password</em>';
$PMF_LANG['rightsLanguage']['editconfig'] = 'editar a configuração';
$PMF_LANG['rightsLanguage']['addatt'] = 'adicionar anexo';
$PMF_LANG['rightsLanguage']['delatt'] = 'apagar anexo';
$PMF_LANG['rightsLanguage']['backup'] = 'criar <em>backup</em>';
$PMF_LANG['rightsLanguage']['restore'] = 'restaurar <em>backup</em>';
$PMF_LANG['rightsLanguage']['delquestion'] = 'apagar questões em aberto';
$PMF_LANG['rightsLanguage']['changebtrevs'] = 'editar revisão';

$PMF_LANG['msgAttachedFiles'] = 'ficheiros anexados:';

// Added v1.3.3 - 27.05.2003 - Thorsten
$PMF_LANG['ad_user_action'] = 'Ação';
$PMF_LANG['ad_entry_email'] = '<em>E-mail</em>:';
$PMF_LANG['ad_entry_allowComments'] = 'Permitir comentários:';
$PMF_LANG['msgWriteNoComment'] = 'Não pode comentar este artigo';
$PMF_LANG['ad_user_realname'] = 'Nome <small>(completo)</small>:';
$PMF_LANG['ad_export_generate_pdf'] = ' PDF';
$PMF_LANG['ad_export_full_faq'] = 'Exportar as FAQ para um ficheiro-PDF: ';
$PMF_LANG['err_bannedIP'] = 'O seu IP encontra-se barrado.';
$PMF_LANG['err_SaveQuestion'] = 'Campos obrigatórios: <strong>Nome</strong>, <strong><em>E-mail</em></strong>, <strong>Questão</strong> e, quando exigido, o código <strong><a href="http://en.wikipedia.org/wiki/Captcha" title="Informação sobre Captcha na Wikipedia - versão em inglês" target="_blank"><em>Captcha</em></a></strong>.<br /> <br /><a href="javascript:history.back();">Voltar atrás</a><br /> <br /> ';

// added v1.3.4 - 23.07.2003 - Thorsten
$PMF_LANG['ad_entry_fontcolor'] = 'Cor da "fonte": ';
$PMF_LANG['ad_entry_fontsize'] = 'Tamanho (corpo) da "fonte": ';

// added v1.4.0 - 2003-12-04 by Thorsten / Mathias
$LANG_CONF['main.language'] = array(0 => 'select', 1 => 'Ficheiro de Idioma');
$LANG_CONF['main.languageDetection'] = array(0 => 'checkbox', 1 => 'Ativar a negociação automática de conteúdo');
$LANG_CONF['main.titleFAQ'] = array(0 => 'input', 1 => 'Título das FAQ');
$LANG_CONF['main.currentVersion'] = array(0 => 'print', 1 => 'Versão do servidor de FAQ');
$LANG_CONF['main.metaDescription'] = array(0 => 'input', 1 => 'Descrição da página');
$LANG_CONF['main.metaKeywords'] = array(0 => 'input', 1 => 'Palavras-Chave para Robôs de Indexação <small>(<em>Spiders </em>)</small>');
$LANG_CONF['main.metaPublisher'] = array(0 => 'input', 1 => 'Nome do Editor');
$LANG_CONF['main.administrationMail'] = array(0 => 'input', 1 => '<em>E-mail</em> do Administrador');
$LANG_CONF['main.contactInformations'] = array(0 => 'area', 1 => 'Informação de contacto');
$LANG_CONF['main.send2friendText'] = array(0 => 'area', 1 => 'Texto para a página <em>send2friend</em>');
$LANG_CONF['records.maxAttachmentSize'] = array(0 => 'input', 1 => 'Tamanho máximo do anexo em Bytes <small><strong>(máx.: %sByte)</strong></small>');
$LANG_CONF['records.disableAttachments'] = array(0 => 'checkbox', 1 => 'Colocar <em>links</em> para anexos debaixo das entradas?');
$LANG_CONF['main.enableUserTracking'] = array(0 => 'checkbox', 1 => 'Usar <em>Tracking</em>?');
$LANG_CONF['main.enableAdminLog'] = array(0 => 'checkbox', 1 => 'Usar o <em>Adminlog</em>?');
$LANG_CONF['security.ipCheck'] = array(0 => 'checkbox', 1 => 'Conferir o IP no modo de Administração ?');
$LANG_CONF['main.numberOfRecordsPerPage'] = array(0 => 'input', 1 => 'Número de tópicos mostrados por página');
$LANG_CONF['main.numberOfShownNewsEntries'] = array(0 => 'input', 1 => 'Número de notícias');
$LANG_CONF['security.bannedIPs'] = array(0 => 'area', 1 => 'Barrar estes IPs');
$LANG_CONF['main.enableRewriteRules'] = array(0 => 'checkbox', 1 => 'Ativar o suporte de <em>mod_rewrite</em> ? <small>(pré-definição: desativado)</small>');
$LANG_CONF['security.ldapSupport'] = array(0 => 'checkbox', 1 => 'Ativar o suporte para LDAP? <small>(pré-definição: desativado)</small>');
$LANG_CONF['main.referenceURL'] = array(0 => 'input', 1 => 'URL-base para verificação de <em>links</em> <small>( ex.: http://www.example.org/faq )</small>');
$LANG_CONF['main.urlValidateInterval'] = array(0 => 'input', 1 => 'Intervalo entre verificações de <em>links</em> pelo AJAX <small>(segundos)</small>');
$LANG_CONF['records.enableVisibilityQuestions'] = array(0 => 'checkbox', 1 => 'Desativar a visibilidade de novos artigos?');
$LANG_CONF['security.permLevel'] = array(0 => 'select', 1 => 'Nível de Permissão');

$PMF_LANG['ad_categ_new_main_cat'] = ' como uma nova categoria principal <small>(i.e., de topo)</small>';
$PMF_LANG['ad_categ_paste_error'] = 'Não é possível mover esta categoria.';
$PMF_LANG['ad_categ_move'] = 'mover categoria';
$PMF_LANG['ad_categ_lang'] = 'Idioma';
$PMF_LANG['ad_categ_desc'] = 'Descrição';
$PMF_LANG['ad_categ_change'] = 'Trocar com ';

$PMF_LANG['lostPassword'] = '<em>Password</em> esquecida? Clique aqui.';
$PMF_LANG['lostpwd_err_1'] = 'Erro: o par <em>Username / e-mail</em> não foi encontrado.';
$PMF_LANG['lostpwd_err_2'] = 'Erro: Entradas inválidas.';
$PMF_LANG['lostpwd_text_1'] = 'Obrigado por requerer informação sobre a sua conta.';
$PMF_LANG['lostpwd_text_2'] = 'Por favor defina uma nova <em>password</em> pessoal na secção de administração das FAQ.';
$PMF_LANG['lostpwd_mail_okay'] = 'O <em>e-mail</em> foi enviado.';

$PMF_LANG['ad_xmlrpc_button'] = 'Obter a referência da versão mais recente do phpMyFAQ';
$PMF_LANG['ad_xmlrpc_latest'] = 'Versão mais recente disponível em ';

// added v1.5.0 - 2005-07-31 by Thorsten
$PMF_LANG['ad_categ_select'] = 'Escolha o idioma da Categoria';

// added v1.5.1 - 2005-09-06 by Thorsten
$PMF_LANG['msgSitemap'] = 'Mapa do <em>Site</em>';

// added v1.5.2 - 2005-09-23 by Lars
$PMF_LANG['err_inactiveArticle'] = 'Esta entrada está a ser revista e não pode ser mostrada.';
$PMF_LANG['msgArticleCategories'] = 'Categorias para esta entrada';

// added v1.6.0 - 2006-02-02 by Thorsten
$PMF_LANG['ad_entry_solution_id'] = 'ID de solução Único';
$PMF_LANG['ad_entry_faq_record'] = 'Registo da FAQ';
$PMF_LANG['ad_entry_new_revision'] = 'Criar uma nova revisão?';
$PMF_LANG['ad_entry_record_administration'] = 'Administração de Registos';
$PMF_LANG['ad_entry_changelog'] = '<em>Changelog</em>';
$PMF_LANG['ad_entry_revision'] = 'Revisão';
$PMF_LANG['ad_changerev'] = 'Selecione uma Revisão';
$PMF_LANG['msgCaptcha'] = 'Por favor insira os carateres que aparecem na imagem';
$PMF_LANG['msgSelectCategories'] = 'A pesquisar em …';
$PMF_LANG['msgAllCategories'] = '… todas as categorias';
$PMF_LANG['ad_you_should_update'] = 'Possui um phpMyFAQ antigo. Deve atualizá-lo para a versão mais recente.';
$PMF_LANG['msgAdvancedSearch'] = 'Pesquisa avançada';

// added v1.6.1 - 2006-04-25 by Matteoï¿½andï¿½Thorsten
$PMF_LANG['spamControlCenter'] = 'Centro de controlo de <em>Spam</em>';
$LANG_CONF['spam.enableSafeEmail'] = array(0 => 'checkbox', 1 => 'Mostrar o <em>e-mail</em> do utilizador de forma segura <small>(pré-definição: ativo)</small>');
$LANG_CONF['spam.checkBannedWords'] = array(0 => 'checkbox', 1 => 'Verificar o conteúdo dos formulários públicos usando a lista de palavras banidas <small>(pré-definição: ativo)</small>');
$LANG_CONF['spam.enableCaptchaCode'] = array(0 => 'checkbox', 1 => 'Usar <em>catpcha</em> para permitir o envio de formulários públicos <small>(pré-definição: ativo)</small>');
$PMF_LANG['ad_session_expiring'] = 'A sua sessão expira daqui a %d minutos: deseja continuar a trabalhar?';

// added v1.6.2 - 2006-06-13 by Matteo
$PMF_LANG['ad_stat_management'] = 'Gestão de Sessões';
$PMF_LANG['ad_stat_choose'] = 'Ano e Mês';
$PMF_LANG['ad_stat_delete'] = 'Apagar imediatamente as sessões selecionadas';

// added v2.0.0 - 2005-09-15 by Thorsten and by Minoru TODA
$PMF_LANG['ad_menu_glossary'] = 'Glossário';
$PMF_LANG['ad_glossary_add'] = 'Adicionar entrada no glossário';
$PMF_LANG['ad_glossary_edit'] = 'Editar entrada do glossário';
$PMF_LANG['ad_glossary_item'] = 'Termo ';
$PMF_LANG['ad_glossary_definition'] = 'Definição';
$PMF_LANG['ad_glossary_save'] = 'Gravar a entrada';
$PMF_LANG['ad_glossary_save_success'] = 'A entrada do Glossário foi gravada.';
$PMF_LANG['ad_glossary_save_error'] = 'A entrada do Glossário <strong>não foi</strong> gravada devido à ocorrência de um erro.';
$PMF_LANG['ad_glossary_update_success'] = 'A entrada do Glossário foi atualizada.';
$PMF_LANG['ad_glossary_update_error'] = 'A entrada do Glossário <strong>não foi</strong> atualizada devido à ocorrência de um erro.';
$PMF_LANG['ad_glossary_delete'] = 'Apagar entrada';
$PMF_LANG['ad_glossary_delete_success'] = 'A entrada do Glossário foi <strong>apagada</strong>.';
$PMF_LANG['ad_glossary_delete_error'] = 'A entrada do Glossário <strong>não foi</strong> apagada devido à ocorrência de um erro.';
$PMF_LANG['ad_linkcheck_noReferenceURL'] = 'A verificação automática de <em>links</em> está desativada (o URL-base para a verificação de <em>links</em> não se encontra definido).';
$PMF_LANG['ad_linkcheck_noAllowUrlOpen'] = 'A verificação automática de <em>links</em> está desativada (a opção do PHP <em>allow_url_fopen</em> não tem o valor <em>Enabled</em>).';
$PMF_LANG['ad_linkcheck_checkResult'] = 'Resultado(s) da verificação automática de <em>links</em> ';
$PMF_LANG['ad_linkcheck_checkSuccess'] = 'OK ';
$PMF_LANG['ad_linkcheck_checkFailed'] = 'Falhou';
$PMF_LANG['ad_linkcheck_failReason'] = 'Causa(s) da(s) falha(s):';
$PMF_LANG['ad_linkcheck_noLinksFound'] = 'Não foram encontrados URLs compatíveis com as funcionalidades do verificador automático de <em>links</em> .';
$PMF_LANG['ad_linkcheck_searchbadonly'] = ' Apenas com <em>links</em> problemáticos';
$PMF_LANG['ad_linkcheck_infoReason'] = 'Informação adicional:';
$PMF_LANG['ad_linkcheck_openurl_infoprefix'] = 'Durante o teste foram (foi) encontrado(s) <strong>%s</strong>: ';
$PMF_LANG['ad_linkcheck_openurl_notready'] = 'O verificador automático de <em>links</em> ( <em>LinkVerifier</em> ) não se encontra preparado para ser utilizado.';
$PMF_LANG['ad_linkcheck_openurl_maxredirect'] = 'O número máximo de redirecionamentos ( <strong>%d</strong> ) foi excedido.';
$PMF_LANG['ad_linkcheck_openurl_urlisblank'] = 'O resultado é um URL em branco.';
$PMF_LANG['ad_linkcheck_openurl_tooslow'] = 'O <em>host</em> <strong>%s</strong> é lento ou não está a responder.';
$PMF_LANG['ad_linkcheck_openurl_nodns'] = 'A resolução DNS do <em>host</em> <strong>%s</strong> é lenta ou falhou devido a problemas relacionados com o DNS (local ou remoto).';
$PMF_LANG['ad_linkcheck_openurl_redirected'] = 'O URL foi redirecionado para <strong>%s</strong>.';
$PMF_LANG['ad_linkcheck_openurl_ambiguous'] = 'Foi obtido um <em>HTTP status</em> ambíguo: <strong>%s</strong>  .';
$PMF_LANG['ad_linkcheck_openurl_not_allowed'] = 'O método <em>HEAD</em> não é suportado pelo <em>host</em> <strong>%s</strong>. Métodos utilizáveis: <strong>%s</strong>.';
$PMF_LANG['ad_linkcheck_openurl_not_found'] = 'O recurso não foi encontrado no <em>host</em> <strong>%s</strong>.';
$PMF_LANG['ad_linkcheck_protocol_unsupported'] = 'O protocolo %s não é suportado pelo verificador automático de <em>links</em>.';
$PMF_LANG['ad_menu_linkconfig'] = 'URL Verifier <small>(verificador automático de <em>links</em>)</small>';
$PMF_LANG['ad_linkcheck_config_title'] = 'Configuração do <em>URL Verifier</em>';
$PMF_LANG['ad_linkcheck_config_disabled'] = 'Verificação automática de <em>links</em> desativada';
$PMF_LANG['ad_linkcheck_config_warnlist'] = 'URLs <em>to warn</em>';
$PMF_LANG['ad_linkcheck_config_ignorelist'] = 'URLs a ignorar';
$PMF_LANG['ad_linkcheck_config_warnlist_description'] = 'Serão sempre emitidas mensagens de aviso para os URLs com os prefixos presentes na lista, quer eles sejam ou não válidos.<br />Utilizar esta funcionalidade para URLs que serão extintos em breve.';
$PMF_LANG['ad_linkcheck_config_ignorelist_description'] = 'Os URLs listados serão considerados sempre válidos (sem verificação).<br />Usar esta funcionalidade para URLs (corretos) que falham na validação do <em>URL Verifier</em>.';
$PMF_LANG['ad_linkcheck_config_th_id'] = 'ID# ';
$PMF_LANG['ad_linkcheck_config_th_url'] = '<em>URL para correspondência</em>';
$PMF_LANG['ad_linkcheck_config_th_reason'] = 'Motivo da correspondência';
$PMF_LANG['ad_linkcheck_config_th_owner'] = '<em>Owner</em> da entrada';
$PMF_LANG['ad_linkcheck_config_th_enabled'] = 'Assinalar para ativar a entrada';
$PMF_LANG['ad_linkcheck_config_th_locked'] = 'Assinalar para bloquear a <em>ownership</em>';
$PMF_LANG['ad_linkcheck_config_th_chown'] = 'Assinalar para obter a <em>ownership</em>';
$PMF_LANG['msgNewQuestionVisible'] = 'A questão tem que ser revista antes de ser disponibilizada ao público em geral.';
$PMF_LANG['msgQuestionsWaiting'] = 'Em lista de espera para análise/publicação pela Administração: ';
$PMF_LANG['ad_entry_visibility'] = 'Publicar?';

// added v2.0.0 - 2006-01-02 by Lars
$PMF_LANG['ad_user_error_password'] = 'Introduza p.f. uma <em>password</em>. ';
$PMF_LANG['ad_user_error_passwordsDontMatch'] = 'A <em>password</em> introduzida nos dois campos não é igual. ';
$PMF_LANG['ad_user_error_loginInvalid'] = 'O nome de utilizador que escolheu não é válido.';
$PMF_LANG['ad_user_error_noEmail'] = 'Por favor, introduza um endereço de <em>e-mail</em> válido. ';
$PMF_LANG['ad_user_error_noRealName'] = 'Introduza o seu nome real <small>(i.e., completo)</small>. ';
$PMF_LANG['ad_user_error_delete'] = 'Não foi possível apagar a conta do utilizador. ';
$PMF_LANG['ad_user_error_noId'] = 'O ID não foi especificado. ';
$PMF_LANG['ad_user_error_protectedAccount'] = 'A conta do utilizador está protegida. ';
$PMF_LANG['ad_user_deleteUser'] = 'Apagar Utilizador';
$PMF_LANG['ad_user_status'] = 'Estado';
$PMF_LANG['ad_user_lastModified'] = 'Alteração mais recente:';
$PMF_LANG['ad_gen_cancel'] = 'Cancelar';
$PMF_LANG['rightsLanguage']['addglossary'] = 'adicionar item ao glossário';
$PMF_LANG['rightsLanguage']['editglossary'] = 'editar item do glossário';
$PMF_LANG['rightsLanguage']['delglossary'] = 'apagar item do glossário';
$PMF_LANG['ad_menu_group_administration'] = 'Grupos';
$PMF_LANG['ad_user_loggedin'] = 'Encontra-se ligado como ';

$PMF_LANG['ad_group_details'] = 'Grupo - Detalhes';
$PMF_LANG['ad_group_add'] = 'Adicionar Grupo';
$PMF_LANG['ad_group_add_link'] = 'Adicionar Grupo';
$PMF_LANG['ad_group_name'] = 'Nome:';
$PMF_LANG['ad_group_description'] = 'Descrição:';
$PMF_LANG['ad_group_autoJoin'] = '<em>Auto-join</em>:';
$PMF_LANG['ad_group_suc'] = 'O Grupo foi <strong>adicionado</strong>.';
$PMF_LANG['ad_group_error_noName'] = 'P.f., atribua um nome ao grupo. ';
$PMF_LANG['ad_group_error_delete'] = 'Não foi possível apagar o Grupo. ';
$PMF_LANG['ad_group_deleted'] = 'O grupo foi <strong>apagado</strong>.';
$PMF_LANG['ad_group_deleteGroup'] = 'Apagar Grupo';
$PMF_LANG['ad_group_deleteQuestion'] = 'Tem a certeza que quer apagar este grupo?';
$PMF_LANG['ad_user_uncheckall'] = 'Desselecionar Tudo';
$PMF_LANG['ad_group_membership'] = 'Grupo(s) a que Pertence';
$PMF_LANG['ad_group_members'] = 'Membros';
$PMF_LANG['ad_group_addMember'] = '+';
$PMF_LANG['ad_group_removeMember'] = '-';

// added v2.0.0 - 2006-07-20 by Matteo
$PMF_LANG['ad_export_which_cat'] = 'Limitar o número de artigos (FAQ) a exportar (opcional)';
$PMF_LANG['ad_export_cat_downwards'] = 'Exportar a(s) categoria(s) abaixo desta?';
$PMF_LANG['ad_export_type'] = 'Formato para exportação';
$PMF_LANG['ad_export_type_choose'] = 'Formato';
$PMF_LANG['ad_export_download_view'] = 'Transferir <small>("descarregar")</small> ou ver no <em>browser</em>?';
$PMF_LANG['ad_export_download'] = ' transferir';
$PMF_LANG['ad_export_view'] = ' ver no <em>browser</em>';
$PMF_LANG['ad_export_gen_xhtml'] = ' XHTML';

// added v2.0.0 - 2006-07-22 by Matteo
$PMF_LANG['ad_news_data'] = 'Conteúdo da Notícia';
$PMF_LANG['ad_news_author_name'] = 'Autor <small>(nome)</small>:';
$PMF_LANG['ad_news_author_email'] = 'Autor <small>(<em>e-mail</em>)</small>:';
$PMF_LANG['ad_news_set_active'] = 'Ativar:';
$PMF_LANG['ad_news_allowComments'] = 'Permitir comentários:';
$PMF_LANG['ad_news_expiration_window'] = ' Período de permanência da notícia (opcional)';
$PMF_LANG['ad_news_from'] = 'De:';
$PMF_LANG['ad_news_to'] = 'Até:';
$PMF_LANG['ad_news_insertfail'] = 'Ocorreu um erro ao inserir o item da notícia na base de dados.';
$PMF_LANG['ad_news_updatefail'] = 'Ocorreu um erro durante a atualização do item da notícia na base de dados.';
$PMF_LANG['newsShowCurrent'] = 'Mostrar as notícias atuais.';
$PMF_LANG['newsShowArchive'] = 'Mostrar as notícias arquivadas.';
$PMF_LANG['newsArchive'] = ' Arquivo de Notícias';
$PMF_LANG['newsWriteComment'] = 'Comentar esta entrada';
$PMF_LANG['newsCommentDate'] = 'Adicionado em: ';

// added v2.0.0 - 2006-07-29 by Matteo & Thorsten
$PMF_LANG['ad_record_expiration_window'] = 'Período de permanência do registo (opcional)';
$PMF_LANG['admin_mainmenu_home'] = 'Quadro';
$PMF_LANG['admin_mainmenu_users'] = 'Utilizadores';
$PMF_LANG['admin_mainmenu_content'] = 'Conteúdo';
$PMF_LANG['admin_mainmenu_statistics'] = 'Estatísticas';
$PMF_LANG['admin_mainmenu_exports'] = 'Exportação';
$PMF_LANG['admin_mainmenu_backup'] = '<em>Backup</em>';
$PMF_LANG['admin_mainmenu_configuration'] = 'Configuração';
$PMF_LANG['admin_mainmenu_logout'] = 'Sair';

// added v2.0.0 - 2006-08-15 by Thorsten and Matteo
$PMF_LANG['ad_categ_owner'] = '<em>Owner</em> da Categoria';
$PMF_LANG['adminSection'] = 'Administração';
$PMF_LANG['err_expiredArticle'] = 'O período de permanência da entrada expirou pelo que ela não pode ser mostrada.';
$PMF_LANG['err_expiredNews'] = 'O período de permanência da notícia expirou pelo que ela não pode ser mostrada.';
$PMF_LANG['err_inactiveNews'] = 'Esta notícia está a ser revista pelo que não pode ser mostrada.';
$PMF_LANG['msgSearchOnAllLanguages'] = 'Pesquisar em todos os idiomas:';
$PMF_LANG['ad_entry_tags'] = 'Etiquetas';
$PMF_LANG['msg_tags'] = 'Etiquetas';

// added v2.0.0 - 2006-09-03 by Matteo
$PMF_LANG['ad_linkcheck_feedback_url-batch1'] = 'A verificar…';
$PMF_LANG['ad_linkcheck_feedback_url-batch2'] = 'A verificar…';
$PMF_LANG['ad_linkcheck_feedback_url-batch3'] = 'A verificar…';
$PMF_LANG['ad_linkcheck_feedback_url-checking'] = 'A verificar…';
$PMF_LANG['ad_linkcheck_feedback_url-disabled'] = 'Desativado';
$PMF_LANG['ad_linkcheck_feedback_url-linkbad'] = '<em>Links</em> KO (i.e. com problemas)';
$PMF_LANG['ad_linkcheck_feedback_url-linkok'] = '<em>Links</em> OK';
$PMF_LANG['ad_linkcheck_feedback_url-noaccess'] = 'Sem acesso';
$PMF_LANG['ad_linkcheck_feedback_url-noajax'] = 'O AJAX não se encontra instalado';
$PMF_LANG['ad_linkcheck_feedback_url-nolinks'] = 'Sem <em>Links</em>';
$PMF_LANG['ad_linkcheck_feedback_url-noscript'] = 'Sem <em>Script</em>';

// added v2.0.0 - 2006-09-02 by Thomas
$PMF_LANG['msg_related_articles'] = 'Entradas relacionadas';
$LANG_CONF['records.numberOfRelatedArticles'] = array(0 => 'input', 1 => 'Número de entradas relacionadas');

// added v2.0.0 - 2006-09-09 by Rudi
$PMF_LANG['ad_categ_trans_1'] = 'Traduzir';
$PMF_LANG['ad_categ_trans_2'] = 'Categoria';
$PMF_LANG['ad_categ_translatecateg'] = 'Traduzir Categoria';
$PMF_LANG['ad_categ_translate'] = 'Traduzir';
$PMF_LANG['ad_categ_transalready'] = 'Já se encontra traduzida em: ';
$PMF_LANG['ad_categ_deletealllang'] = 'Apagar em todos os idiomas?';
$PMF_LANG['ad_categ_deletethislang'] = 'Apagar apenas neste idioma?';
$PMF_LANG['ad_categ_translated'] = 'A categoria foi traduzida.';

// added v2.0.0 - 2006-09-21 by Rudi
$PMF_LANG['ad_categ_show'] = 'Visão Geral';
$PMF_LANG['ad_menu_categ_structure'] = 'Visão geral das Categorias <small>(incluindo os seus idiomas)</small>';

// added v2.0.0 - 2006-09-26 by Thorsten
$PMF_LANG['ad_entry_userpermission'] = 'Permissões de Utilizador:';
$PMF_LANG['ad_entry_grouppermission'] = 'Permissões de Grupo:';
$PMF_LANG['ad_entry_all_users'] = 'Acessível por todos os utilizadores';
$PMF_LANG['ad_entry_restricted_users'] = 'Acesso restrito a';
$PMF_LANG['ad_entry_all_groups'] = 'Acessível por todos os grupos';
$PMF_LANG['ad_entry_restricted_groups'] = 'Acesso restrito a';
$PMF_LANG['ad_session_expiration'] = 'Tempo disponível até a sua sessão expirar';
$PMF_LANG['ad_user_active'] = 'ativo';
$PMF_LANG['ad_user_blocked'] = 'bloqueado';
$PMF_LANG['ad_user_protected'] = 'protegido';

// added v2.0.0 - 2006-10-07 by Matteo
$PMF_LANG['ad_entry_intlink'] = 'Selecione um registo de FAQ para o inserir como <em>link</em>…';

//added 2.0.0 - 2006-10-10 by Rudi
$PMF_LANG['ad_categ_paste2'] = 'Colar depois de';
$PMF_LANG['ad_categ_remark_move'] = 'Só é possível trocar a ordem de 2 categorias se elas se encontrem no mesmo nível.';
$PMF_LANG['ad_categ_remark_overview'] = '<em>Nota</em>: só se pode obter e apresentar a <em>ordenação correta</em> das categorias caso <em>todas existam</em> no idioma atual (primeira coluna).';

// added v2.0.0 - 2006-10-15 by Matteo
$PMF_LANG['msgUsersOnline'] = '%d Convidado(s) e %d Registado(s)';
$PMF_LANG['ad_adminlog_del_older_30d'] = 'Apagar imediatamente os <em>logs</em> com mais de 30 dias';
$PMF_LANG['ad_adminlog_delete_success'] = 'Os <em>logs</em> antigos foram <strong>apagados</strong>.';
$PMF_LANG['ad_adminlog_delete_failure'] = 'Não foi apagado nenhum <em>log</em>: ocorreu um erro durante o pedido de execução da instrução.';

// added 2.0.0 - 2006-11-19 by Thorsten
$PMF_LANG['opensearch_plugin_install'] = 'adicionar o <em>plug-in</em> de pesquisa nestas FAQ ao seu <em>browser</em>';
$PMF_LANG['ad_quicklinks'] = 'Ligações rápidas';
$PMF_LANG['ad_quick_category'] = 'Adicionar uma nova categoria';
$PMF_LANG['ad_quick_record'] = 'Adicionar uma nova FAQ';
$PMF_LANG['ad_quick_user'] = 'Criar um novo utilizador';
$PMF_LANG['ad_quick_group'] = 'Criar um novo grupo';

// added v2.0.0 - 2006-12-30 by Matteo
$PMF_LANG['msgNewTranslationHeader'] = 'Proposta de Tradução';
$PMF_LANG['msgNewTranslationAddon'] = 'A sua proposta não será publicada imediatamente mas será avaliada pelos editores. Os campos obrigatórios são: <strong>Nome</strong>, <strong><em>E-mail</em></strong>, <strong>Cabeçalho</strong> <small>(tema)</small> traduzido e <strong>Conteúdo</strong> traduzido. As palavras-chave têm que ser separadas apenas por vírgulas.';
$PMF_LANG['msgNewTransSourcePane'] = 'Original';
$PMF_LANG['msgNewTranslationPane'] = 'Tradução';
$PMF_LANG['msgNewTranslationName'] = 'Nome:';
$PMF_LANG['msgNewTranslationMail'] = '<em>E-mail</em>:';
$PMF_LANG['msgNewTranslationKeywords'] = 'Palavras-Chave:';
$PMF_LANG['msgNewTranslationSubmit'] = 'Enviar a sua proposta';
$PMF_LANG['msgTranslate'] = 'Propor uma tradução para';
$PMF_LANG['msgTranslateSubmit'] = 'Iniciar a tradução…';
$PMF_LANG['msgNewTranslationThanks'] = 'Obrigado pela sua proposta de tradução.';

// added v2.0.0 - 2007-02-27 by Matteo
$PMF_LANG['rightsLanguage']['addgroup'] = 'criar contas de grupos';
$PMF_LANG['rightsLanguage']['editgroup'] = 'editar contas de grupos';
$PMF_LANG['rightsLanguage']['delgroup'] = 'apagar contas de grupos';

// added v2.0.0 - 2007-02-27 by Thorsten
$PMF_LANG['ad_news_link_parent'] = ' O <em>link</em> será aberto na janela inicial';

// added v2.0.0 - 2007-03-04 by Thorsten
$PMF_LANG['ad_menu_comments'] = 'Comentários';
$PMF_LANG['ad_comment_administration'] = 'Administração de Comentários';
$PMF_LANG['ad_comment_faqs'] = 'Comentários nos registos de FAQ:';
$PMF_LANG['ad_comment_news'] = 'Comentários nos registos de Notícias:';
$PMF_LANG['ad_groups'] = 'Grupos';

// added v2.0.0 - 2007-03-10 by Thorsten
$LANG_CONF['records.orderby'] = array(0 => 'select', 1 => 'Ordenação dos registos <small>(de acordo com as <strong>propriedades</strong>)</small>');
$LANG_CONF['records.sortby'] = array(0 => 'select', 1 => 'Ordenação dos registos <small>(ascendente ou descendente)</small>');
$PMF_LANG['ad_conf_order_id'] = 'ID (pré-definido)';
$PMF_LANG['ad_conf_order_thema'] = 'Título';
$PMF_LANG['ad_conf_order_visits'] = 'Número de visitantes';
$PMF_LANG['ad_conf_order_datum'] = 'Data';
$PMF_LANG['ad_conf_order_author'] = 'Autor';
$PMF_LANG['ad_conf_desc'] = 'descendente';
$PMF_LANG['ad_conf_asc'] = 'ascendente';
$PMF_LANG['mainControlCenter'] = 'Configuração Principal';
$PMF_LANG['recordsControlCenter'] = 'Configuração dos registos das FAQ';

// added v2.0.0 - 2007-03-17 by Thorsten
$PMF_LANG['msgInstantResponse'] = 'Resposta Imediata';
$PMF_LANG['msgInstantResponseMaxRecords'] = '.<br />Lista da(s) primeira(s) %d entrada(s):';

// added v2.0.0 - 2007-03-29 by Thorsten
$LANG_CONF['records.defaultActivation'] = array(0 => 'checkbox', 1 => 'Ativar automaticamente os novos registos <small>(pré-definição: desativada)</small>');
$LANG_CONF['records.defaultAllowComments'] = array(0 => 'checkbox', 1 => 'Permitir comentários nos novos registos <small>(pré-definição: desativada)</small>');

// added v2.0.0 - 2007-04-04 by Thorsten
$PMF_LANG['msgAllCatArticles'] = 'Registos nesta categoria';
$PMF_LANG['msgDescriptionInstantResponse'] = 'Simples: escreva a expressão e verifique as sugestões que vão surgindo…';
$PMF_LANG['msgTagSearch'] = 'Entradas etiquetadas';
$PMF_LANG['ad_pmf_info'] = 'Informação sobre o phpMyFAQ';
$PMF_LANG['ad_online_info'] = 'Verificação <em>online</em> da versão mais recente do phpMyFAQ';
$PMF_LANG['ad_system_info'] = 'Informação sobre o Sistema';

// added 2.5.0-alpha - 2008-01-25 by Elger
$PMF_LANG['msgRegisterUser'] = 'Deseja registar-se?';
$PMF_LANG['ad_user_loginname'] = 'Nome <small>(de <em>login</em>)</small>:';
$PMF_LANG['errorRegistration'] = 'Este campo é obrigatório';
$PMF_LANG['submitRegister'] = 'Enviar Registo';
$PMF_LANG['msgUserData'] = 'Informação sobre o Utilizador obrigatória para registo';
$PMF_LANG['captchaError'] = 'P.f., introduza corretamente o código <em>captcha</em> .';
$PMF_LANG['msgRegError'] = 'Ocorreram alguns erros; corrija-os p.f.:';
$PMF_LANG['successMessage'] = 'O seu registo foi gravado. Receberá em breve um <em>e-mail</em> de confirmação com detalhes do seu <em>login</em>.';
$PMF_LANG['msgRegThankYou'] = 'Obrigado por se ter registado';
$PMF_LANG['emailRegSubject'] = '[%sitename%] Registo: novo utilizador';

// added 2.5.0-alpha2 - 2009-01-24 by Thorsten
$PMF_LANG['msgMostPopularSearches'] = 'Pesquisas mais frequentes:';
$LANG_CONF['main.enableWysiwygEditor'] = array(0 => 'checkbox', 1 => 'Ativar o editor gráfico incluído <small>(pré-definição: ativo)</small>');

// added 2.5.0-beta - 2009-03-30 by Anatoliy
$PMF_LANG['ad_menu_searchstats'] = 'Estatísticas de Pesquisas';
$PMF_LANG['ad_searchstats_search_term'] = 'Palavra(s)-Chave';
$PMF_LANG['ad_searchstats_search_term_count'] = 'Contagem';
$PMF_LANG['ad_searchstats_search_term_lang'] = 'Idioma';
$PMF_LANG['ad_searchstats_search_term_percentage'] = 'Percentagem';

// added 2.5.0-beta - 2009-03-31 by Anatoliy
$PMF_LANG['ad_record_sticky'] = 'Permanente';
$PMF_LANG['ad_entry_sticky'] = 'Permanente';
$PMF_LANG['stickyRecordsHeader'] = 'Permanentes';

// added 2.5.0-beta - 2009-04-01 by Anatoliy
$PMF_LANG['ad_menu_stopwordsconfig'] = '<em>Stop Words</em>';
$PMF_LANG['ad_config_stopword_input'] = 'Adicionar uma nova <em>stop word</em>';

// added 2.5.0-beta - 2009-04-06 by Anatoliy
$PMF_LANG['msgSendMailDespiteEverything'] = 'Não; ainda não existe uma resposta adequada (enviá-la-ei no <em>e-mail</em>)';
$PMF_LANG['msgSendMailIfNothingIsFound'] = 'A resposta pretendida encontra-se na lista anterior?';

// added 2.5.0-RC - 2009-05-11 by Anatoliy & Thorsten
$PMF_LANG['msgChooseLanguageToTranslate'] = 'Escolha p.f. o idioma de tradução';
$PMF_LANG['msgLangDirIsntWritable'] = 'Não é possível efetuar gravações na pasta de Traduções.';
$PMF_LANG['ad_menu_translations'] = 'Interface de Tradução';
$PMF_LANG['ad_start_notactive'] = 'À espera de ser ativada(o)';

// added 2.5.0-RC - 2009-05-20 by Anatoliy
$PMF_LANG['msgTransToolAddNewTranslation'] = 'Adicionar um novo idioma de tradução';
$PMF_LANG['msgTransToolLanguage'] = 'Idioma <small>("Língua")</small>';
$PMF_LANG['msgTransToolActions'] = 'Ações';
$PMF_LANG['msgTransToolWritable'] = 'Editável';
$PMF_LANG['msgEdit'] = 'Editar';
$PMF_LANG['msgDelete'] = 'Apagar';
$PMF_LANG['msgYes'] = 'Sim';
$PMF_LANG['msgNo'] = 'Não';
$PMF_LANG['msgTransToolSureDeleteFile'] = 'Tem a certeza que quer apagar este ficheiro de idioma?';
$PMF_LANG['msgTransToolFileRemoved'] = 'O ficheiro de idioma foi <strong>removido</em>.';
$PMF_LANG['msgTransToolErrorRemovingFile'] = 'Ocorreu um erro durante a remoção do ficheiro de idioma.';
$PMF_LANG['msgVariable'] = 'Variável';
$PMF_LANG['msgCancel'] = 'Cancelar';
$PMF_LANG['msgSave'] = 'Gravar';
$PMF_LANG['msgSaving3Dots'] = 'A gravar…';
$PMF_LANG['msgRemoving3Dots'] = 'A remover…';
$PMF_LANG['msgTransToolFileSaved'] = 'O ficheiro de idioma foi gravado';
$PMF_LANG['msgTransToolErrorSavingFile'] = 'Ocorreu um erro ao gravar o ficheiro de idioma.';
$PMF_LANG['msgLanguage'] = 'Idioma';
$PMF_LANG['msgTransToolLanguageCharset'] = '<em>Charset</em> do Idioma';
$PMF_LANG['msgTransToolLanguageDir'] = 'Direção de escrita do Idioma';
$PMF_LANG['msgTransToolLanguageDesc'] = 'Descrição do Idioma';
$PMF_LANG['msgTransToolAddAuthor'] = 'Adicionar autor';
$PMF_LANG['msgTransToolCreateTranslation'] = 'Criar Tradução';
$PMF_LANG['msgTransToolTransCreated'] = 'A nova tradução foi criada';
$PMF_LANG['msgTransToolCouldntCreateTrans'] = 'Não foi possível criar a nova tradução';
$PMF_LANG['msgAdding3Dots'] = 'A adicionar…';
$PMF_LANG['msgTransToolSendToTeam'] = 'Enviar para a equipa de desenvolvimento do phpMyFAQ';
$PMF_LANG['msgSending3Dots'] = 'A enviar…';
$PMF_LANG['msgTransToolFileSent'] = 'O ficheiro de idioma foi enviado para a equipa do phpMyFAQ. Obrigado por o partilhar.';
$PMF_LANG['msgTransToolErrorSendingFile'] = 'Ocorreu um erro durante o envio do ficheiro de idioma.';
$PMF_LANG['msgTransToolPercent'] = 'Percentagem';

// added 2.5.0-RC3 - 2009-06-23 by Anatoliy
$LANG_CONF['records.attachmentsPath'] = array(0 => 'input', 1 => '<em>Path</em> para o local de gravação de anexos.<br /><small>Um <em>path</em> relativo refere-se a uma pasta dentro do <em>web root</em></small>');

// added 2.5.0-RC3 - 2009-06-24 by Anatoliy
$PMF_LANG['msgAttachmentNotFound'] = 'O ficheiro que pretende transferir não se encontra neste servidor.';
$PMF_LANG['ad_sess_noentry'] = 'Sem entradas';

// added 2.6.0-alpha - 2009-07-30 by Aurimas Fišeras
//P.S. "One User online" is also possible, since sprintf just ignores extra args
$PMF_LANG['plmsgUserOnline'][0] = '%d utilizador <em>online</em>';
$PMF_LANG['plmsgUserOnline'][1] = '%d utilizadores <em>online</em>';

// added 2.6.0-alpha - 2009-08-02 by Anatoliy
$LANG_CONF['main.templateSet'] = array(0 => 'select', 1 => 'Conjunto de Modelos (<em>Templates</em>) a usar');

// added 2.6.0-alpha - 2009-08-16 by Aurimas Fišeras
$PMF_LANG['msgTransToolRemove'] = 'Remover';
$PMF_LANG['msgTransToolLanguageNumberOfPlurals'] = 'Número de variantes do plural';
$PMF_LANG['msgTransToolLanguageOnePlural'] = 'Este idioma tem apenas uma variante do plural.';
$PMF_LANG['msgTransToolLanguagePluralNotSet'] = 'O suporte de variantes do plural para o idioma  %s encontra-se desativado (o valor de <em>nplurals</em> não se encontra definido).';

// added 2.6.0-alpha - 2009-08-16 by Aurimas Fišeras - Plural messages
$PMF_LANG['plmsgHomeArticlesOnline'][0] = 'Existe %d artigo disponível <em>on-line</em>';
$PMF_LANG['plmsgHomeArticlesOnline'][1] = 'Existem %d artigos disponíveis <em>on-line</em>';
$PMF_LANG['plmsgViews'][0] = '%d visualização';
$PMF_LANG['plmsgViews'][1] = '%d visualizações';

// added 2.6.0-alpha - 2009-08-30 by Aurimas Fišeras - Plural messages
$PMF_LANG['plmsgGuestOnline'][0] = ' <br /> %d Convidado';
$PMF_LANG['plmsgGuestOnline'][1] = ' <br /> %d Convidados';
$PMF_LANG['plmsgRegisteredOnline'][0] = ' e %d Registado';
$PMF_LANG['plmsgRegisteredOnline'][1] = ' e %d Registados';
$PMF_LANG['plmsgSearchAmount'][0] = '%d resultado da pesquisa';
$PMF_LANG['plmsgSearchAmount'][1] = '%d resultados da pesquisa';
$PMF_LANG['plmsgPagesTotal'][0] = ' %d Página';
$PMF_LANG['plmsgPagesTotal'][1] = ' %d Páginas';
$PMF_LANG['plmsgVotes'][0] = '%d Avaliação';
$PMF_LANG['plmsgVotes'][1] = '%d Avaliações';
$PMF_LANG['plmsgEntries'][0] = '%d FAQ ';
$PMF_LANG['plmsgEntries'][1] = '%d FAQ ';

// added 2.6.0-alpha - 2009-09-06 by Aurimas Fišeras
$PMF_LANG['rightsLanguage']['addtranslation'] = 'adicionar traduções';
$PMF_LANG['rightsLanguage']['edittranslation'] = 'editar traduções';
$PMF_LANG['rightsLanguage']['deltranslation'] = 'apagar traduções';
$PMF_LANG['rightsLanguage']['approverec'] = 'aprovar registos';

// added 2.6.0-alpha - 2009-09-9 by Anatoliy Belsky
$LANG_CONF['records.enableAttachmentEncryption'] = array(0 => 'checkbox', 1 => 'Ativar a cifragem (encriptação) de anexos<br /><small>(não será usada caso a opção de anexação de ficheiros se encontrar desativada)</small>');
$LANG_CONF['records.defaultAttachmentEncKey'] = array(0 => 'input', 1 => 'Chave pré-definida para cifragem (encriptação) dos anexos <br /><small>(não será usada caso a opção de encriptação de anexos se encontrar desativada).</small><br /><small style="color: red"><strong>Atenção</strong>: <strong>Não alterar</strong> depois da chave ter sido definida e a opção de encriptação de anexos ter sido ativada.</small>');
//$LANG_CONF["main.attachmentsStorageType"] = array(0 => "select", 1 => "Attachment storage type");
//$PMF_LANG['att_storage_type'][0] = 'Filesystem';
//$PMF_LANG['att_storage_type'][1] = 'Database';

// added 2.6.0-alpha - 2009-09-06 by Thorsten
$PMF_LANG['ad_menu_upgrade'] = 'Atualizar';
$PMF_LANG['ad_you_shouldnt_update'] = 'Já possui a versão mais recente do phpMyFAQ. Não necessita de a atualizar.';
$LANG_CONF['security.useSslForLogins'] = array(0 => 'checkbox', 1 => 'Permitir a validação apenas no modo seguro? - <small>HTTPS</small> <small>(pré-definição: desativada)</small>');
$PMF_LANG['msgSecureSwitch'] = 'Para se validar tem que utilizar uma ligação segura (via <small>HTTPS</small>).';

// added 2.6.0-alpha - 2009-10-03 by Anatoliy Belsky
$PMF_LANG['msgTransToolNoteFileSaving'] = 'Atenção: nenhum ficheiro/alteração será gravado(a) antes de carregar no botão <em>Gravar</em>';
$PMF_LANG['msgTransToolPageBufferRecorded'] = 'A página %d do <em>buffer<em> foi gravada.';
$PMF_LANG['msgTransToolErrorRecordingPageBuffer'] = 'Ocorreu um erro durante a gravação da página %d do <em>buffer<em>.';
$PMF_LANG['msgTransToolRecordingPageBuffer'] = 'A gravar a página %d do <em>buffer<em>.';

// added 2.6.0-alpha - 2009-11-02 by Anatoliy Belsky
$PMF_LANG['ad_record_active'] = 'Ativo';

// added 2.6.0-alpha - 2009-11-01 by Anatoliy Belsky
$PMF_LANG['msgAttachmentInvalid'] = 'O anexo escolhido não é válido: informe p.f. o Administrador.';

// added 2.6.0-alpha - 2009-11-02 by max
$LANG_CONF['seach.numberSearchTerms'] = array(0 => 'input', 1 => 'Número de termos de pesquisa listados');
$LANG_CONF['records.orderingPopularFaqs'] = array(0 => 'select', 1 => 'Ordenação do TOP 10');
$PMF_LANG['list_all_users'] = 'Listar todos os utilizadores';

$PMF_LANG['records.orderingPopularFaqs.visits'] = 'Listar as entradas mais visitadas';
$PMF_LANG['records.orderingPopularFaqs.voting'] = 'Listar as entradas com maior número de avaliações';

// added 2.6.0-alpha - 2009-11-05 by Thorsten
$PMF_LANG['msgShowHelp'] = 'Separe as palavras por vírgulas.';

// added 2.6.0-RC - 2009-11-30 by Thorsten
$PMF_LANG['msgUpdateFaqDate'] = 'Atualizar';
$PMF_LANG['msgKeepFaqDate'] = 'Manter';
$PMF_LANG['msgEditFaqDat'] = 'Editar';
$LANG_CONF['main.optionalMailAddress'] = array(0 => 'checkbox', 1 => 'Marcar como obrigatório o campo do endereço de <em>e-mail</em> <small>(pré-definição: desativada)</small>');
$LANG_CONF['search.useAjaxSearchOnStartpage'] = array(0 => 'checkbox', 1 => 'Resposta Imediata na página inicial <small>(pré-definição: desativada)</small>');

// added v2.6.99 - 2010-11-24 by Gustavo Solt
$LANG_CONF['search.relevance'] = array(0 => 'select', 1 => 'Ordenar por relevância');
$LANG_CONF['search.enableRelevance'] = array(0 => 'checkbox', 1 => 'Ativar a utilização da relevância? <small>(pré-definição: desativada)</small>');
$PMF_LANG['searchControlCenter'] = 'Pesquisa';
$PMF_LANG['search.relevance.thema-content-keywords'] = 'Questão - Resposta - Palavras-Chave';
$PMF_LANG['search.relevance.thema-keywords-content'] = 'Questão - Palavras-Chave - Resposta';
$PMF_LANG['search.relevance.content-thema-keywords'] = 'Resposta - Questão - Palavras-Chave';
$PMF_LANG['search.relevance.content-keywords-thema'] = 'Resposta - Palavras-Chave - Questão';
$PMF_LANG['search.relevance.keywords-content-thema'] = 'Palavras-Chave - Resposta - Questão';
$PMF_LANG['search.relevance.keywords-thema-content'] = 'Palavras-Chave - Questão - Resposta';

// added v2.6.99 - 2010-11-30 by Gustavo Solt
$LANG_CONF['main.enableGoogleTranslation'] = array(0 => 'checkbox', 1 => 'Ativar o <em>Google translations</em> <small>(pré-definição: desativado)</small>');
$LANG_CONF['main.googleTranslationKey'] = array(0 => 'input', 1 => 'Google API key');
$PMF_LANG['msgNoGoogleApiKeyFound'] = 'A <em>Google API key</em> está vazia: indique o seu valor na secção de configuração';

// added 2.7.0-alpha - 2010-09-13 by Thorsten
$PMF_LANG['msgLoginUser'] = '<em>Login</em>';
$PMF_LANG['socialNetworksControlCenter'] = 'Configuração do suporte de Redes Sociais';
$LANG_CONF['socialnetworks.enableTwitterSupport'] = array(0 => 'checkbox', 1 => 'Twitter <small>(pré-definição: desativado)</small>');
$LANG_CONF['socialnetworks.twitterConsumerKey'] = array(0 => 'input', 1 => '<em>Twitter Consumer Key</em>');
$LANG_CONF['socialnetworks.twitterConsumerSecret'] = array(0 => 'input', 1 => '<em>Twitter Consumer Secret</em>');

// added 2.7.0-alpha - 2010-10-14 by Tom Zeithaml
$LANG_CONF['socialnetworks.twitterAccessTokenKey'] = array(0 => 'input', 1 => '<em>Twitter Access Token Key</em>');
$LANG_CONF['socialnetworks.twitterAccessTokenSecret'] = array(0 => 'input', 1 => '<em>Twitter Access Token Secret</em>');
$LANG_CONF['socialnetworks.enableFacebookSupport'] = array(0 => 'checkbox', 1 => 'Facebook <small>(pré-definição: desativado)</small>)');

// added 2.7.0-alpha - 2010-12-21 by Anatoliy Belsky
$PMF_LANG['ad_menu_attachments'] = 'Anexos';
$PMF_LANG['ad_menu_attachment_admin'] = 'Administração de Anexos';
$PMF_LANG['msgAttachmentsFilename'] = 'Nome do ficheiro';
$PMF_LANG['msgAttachmentsFilesize'] = 'Tamanho do ficheiro';
$PMF_LANG['msgAttachmentsMimeType'] = '<em>MIME Type</em>';
$PMF_LANG['msgAttachmentsWannaDelete'] = 'Tem a certeza que quer apagar este anexo?';
$PMF_LANG['msgAttachmentsDeleted'] = 'Anexo <strong>apagado</strong>.';

// added v2.7.0-alpha2 - 2010-01-12 by Gustavo Solt
$PMF_LANG['ad_menu_reports'] = 'Relatórios';
$PMF_LANG['ad_stat_report_fields'] = 'Campos';
$PMF_LANG['ad_stat_report_category'] = 'Categoria';
$PMF_LANG['ad_stat_report_sub_category'] = 'Subcategoria';
$PMF_LANG['ad_stat_report_translations'] = 'Tradução';
$PMF_LANG['ad_stat_report_language'] = 'Idioma';
$PMF_LANG['ad_stat_report_id'] = 'FAQ ID';
$PMF_LANG['ad_stat_report_sticky'] = 'FAQ Permanente';
$PMF_LANG['ad_stat_report_title'] = 'Questão';
$PMF_LANG['ad_stat_report_creation_date'] = 'Data';
$PMF_LANG['ad_stat_report_owner'] = 'Autor original';
$PMF_LANG['ad_stat_report_last_modified_person'] = 'Autor da alteração mais recente';
$PMF_LANG['ad_stat_report_url'] = 'URL';
$PMF_LANG['ad_stat_report_visits'] = 'Visitas';
$PMF_LANG['ad_stat_report_make_report'] = 'Gerar o Relatório';
$PMF_LANG['ad_stat_report_make_csv'] = 'Exportar no formato CSV';

// added v2.7.0-alpha2 - 2010-02-05 by Thorsten Rinne
$PMF_LANG['msgRegistration'] = 'Registo';
$PMF_LANG['msgRegistrationCredentials'] = 'Para se registar é necessário indicar o seu <strong>Nome</strong> <small>(real)</small>, o <strong><em>Username</em></strong> desejado <small>(<em>login</em>)</small> e um <strong><em>E-mail</em></strong> válido.';
$PMF_LANG['msgRegistrationNote'] = 'Caso a operação de registo seja concluída sem erros, receberá uma resposta da Administração indicando se ele foi ou não aceite.';

// added v2.7.0-beta - 2011-06-13 by Thorsten
$PMF_LANG['ad_entry_changelog_history'] = '<em>Changelog</em> - histórico';

// added v2.7.0-beta2 - 2011-06-22 by Thorsten
$LANG_CONF['security.ssoSupport'] = array(0 => 'checkbox', 1 => '<em>Single Sign On Support - SSO</em> <small>(pré-definição: desativado)</small>');
$LANG_CONF['security.ssoLogoutRedirect'] = array(0 => 'input', 1 => '<em>SSO logout redirect service URL</em>');
$LANG_CONF['main.dateFormat'] = array(0 => 'input', 1 => 'Formato da Data e Hora <small>(pré-definição: Y-m-d H:i<br />ex.: 2011-12-31 13:00)</small>');
$LANG_CONF['security.enableLoginOnly'] = array(0 => 'checkbox', 1 => 'Acesso restrito ao conteúdo - <small><em>Complete secured FAQ</em></small> <small>(pré-definição: desativado)</small>');
