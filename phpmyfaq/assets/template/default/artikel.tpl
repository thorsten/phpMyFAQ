<section>
            <article class="pmf-faq">
                <header>
                    <div class="pull-right hidden-print" id="solution_id">
                        <a class="label label-info" href="{solution_id_link}">
                            ID #{solution_id}
                        </a>
                    </div>
                    <h1>{writeThema}</h1>
                    <div class="pmf-faq-meta clearfix">
                        <div class="pmf-faq-author">
                            <i aria-hidden="true" class="fa fa-user"></i>
                            <span class="data">{writeAuthor}</span>
                        </div>
                        <div class="pmf-faq-date">
                            <i aria-hidden="true" class="fa fa-calendar"></i>
                            <span class="data">{writeDateMsg}</span>
                        </div>
                        <div class="pmf-faq-comments">
                            <i aria-hidden="true" class="fa fa-comments"></i>
                            <span class="data"><a class="show-comment-form" href="#">{numberOfComments}</a></span>
                        </div>
                        <div class="pmf-faq-edit">
                            {editThisEntry}
                        </div>
                    </div>
                </header>
                <div class="pmf-faq-body">

                    {writeContent}

                    [tagsAvailable]
                    {renderTags}
                    [/tagsAvailable]
                    [privateNotes]
                    <div class="pmf-private-notes">
                        <h4>
                            {notesHeader}
                        </h4>
                        <p>
                            {notes}
                        </p>
                    </div>
                    [/privateNotes]
                    [relatedFaqs]
                    <aside id="faqAccordion" class="accordion related-faqs hidden-print">
                        <div class="accordion-group">
                            <div class="accordion-heading">
                                <a class="accordion-toggle" href="#collapseRelatedFaqs" data-parent="#faqAccordion" data-toggle="collapse">
                                    {renderRelatedArticlesHeader}
                                </a>
                            </div>
                            <div id="collapseRelatedFaqs" class="accordion-body collapse">
                                <div class="accordion-inner">{renderRelatedArticles}</div>
                            </div>
                        </div>
                    </aside>
                    [/relatedFaqs]
                    [relatedCategories]
                    <aside id="faqAccordion" class="accordion related-categories hidden-print">
                        <div class="accordion-group">
                            <div class="accordion-heading">
                                <a class="accordion-toggle" href="#collapseRelatedCategories" data-parent="#faqAccordion" data-toggle="collapse">
                                    {renderRelatedCategoriesHeader}
                                </a>
                            </div>
                            <div id="collapseRelatedCategories" class="accordion-body collapse">
                                <div class="accordion-inner">{renderRelatedCategories}</div>
                            </div>
                        </div>
                    </aside>
                    [/relatedCategories]

                </div>

            </article>

            <ul id="tab" class="nav nav-tabs hidden-print">
                <li class="active"><a href="#votingForm" data-toggle="tab">{msgVoteUseability}</a></li>
                [switchLanguage]
                <li><a href="#switchAvailableLanguage" data-toggle="tab">{msgChangeLanguage}</a></li>
                [/switchLanguage]
                [addTranslation]
                <li><a href="#addTranslation" data-toggle="tab">{msgTranslate}</a></li>
                [/addTranslation]
            </ul>

            <div class="tab-content pmf-faq-info">
                <div class="tab-pane active hidden-print" id="votingForm">
                    <form action="#" method="post" class="form-inline">
                        <input type="hidden" name="artikel" value="{saveVotingID}">
                        <div id="votings"></div>
                        <div class="pmf-stars">
                            <div class="pmf-star-rating" data-rating="{msgAverageVote}">
                                <span data-stars="5">☆</span>
                                <span data-stars="4">☆</span>
                                <span data-stars="3">☆</span>
                                <span data-stars="2">☆</span>
                                <span data-stars="1">☆</span>
                            </div>
                        </div>

                        <div class="pull-right">
                            <strong>{msgAverageVote}</strong><span id="rating">{printVotings}</span>
                        </div>
                    </form>
                </div>
                <div class="tab-pane hidden-print" id="switchAvailableLanguage">
                    {switchLanguage}
                </div>
                <div class="tab-pane hidden-print" id="addTranslation">
                    <form action="{translationUrl}" method="post" class="form-inline" accept-charset="utf-8">
                        {languageSelection}
                        <button class="btn btn-primary" type="submit" name="submit">
                            {msgTranslateSubmit}
                        </button>
                    </form>
                </div>
            </div>

            <p class="hidden-print">{writeCommentMsg}</p>

            <aside class="pmf-create-comment hide" id="pmf-create-comment">

                <hr>

                <div id="loader"></div>

                <h2><i aria-hidden="true" class="fa fa-heart"></i> {msgCommentHeader}</h2>

                <form id="formValues" action="#" method="post" class="form-horizontal" accept-charset="utf-8">
                    <input type="hidden" name="id" id="id" value="{id}">
                    <input type="hidden" name="lang" id="lang" value="{lang}">
                    <input type="hidden" name="type" id="type" value="faq">

                    <div class="row">
                        <div class="col-md-6">
                            <input type="text" name="user" id="user" class="form-control input-lg" required
                                   placeholder="{msgNewContentName}" value="{defaultContentName}">
                        </div>
                        <div class="col-md-6">
                            <input type="email" name="mail" id="mail" class="form-control input-lg" required
                                   placeholder="{msgNewContentMail}"  value="{defaultContentMail}">
                        </div>
                    </div>

                    <textarea rows="10" name="comment_text" id="comment_text" class="form-control input-lg"
                              placeholder="{msgYourComment}"></textarea>

                    {captchaFieldset}
                    
                    <div class="pmf-buttons clearfix">
                        <button class="btn btn-primary" id="submitcomment" type="submit">
                            {msgNewContentSubmit}
                        </button>
                    </div>
                </form>
            </aside>

            <aside class="pmf-comments" id="comments">
                <hr>
                {writeComments}
            </aside>

        </section>

        <script src="assets/js/libs/highlight.pack.js"></script>
        <link rel="stylesheet" href="assets/js/libs/default.css">
        <script>
            $(document).ready(function() {
                $(function() {
                    $('div.pmf-stars > div.pmf-star-rating > span').on('click', function(e) {
                        var numStars = $(e.target).data('stars');
                        saveVoting('faq', {id}, numStars, '{lang}');
                    });
                });
                $('pre code').each(function(i, block) {
                    hljs.highlightBlock(block);
                });
            });
        </script>
