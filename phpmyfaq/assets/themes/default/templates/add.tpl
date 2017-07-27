<section>
            <p>{msgNewContentAddon}</p>
            <form id="formValues" action="#" method="post" class="form-horizontal" accept-charset="utf-8">
                <input type="hidden" name="lang" id="lang" value="{lang}">
                <input type="hidden" value="{openQuestionID}" id="openQuestionID" name="openQuestionID">
                
                <div class="form-group">
                    <label class="col-sm-3 control-label" for="name">{msgNewContentName}</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" name="name" id="name" value="{defaultContentName}" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label" for="email">{msgNewContentMail}</label>
                    <div class="col-sm-9">
                        <input type="email" class="form-control" name="email" id="email" value="{defaultContentMail}" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label" for="rubrik">{msgNewContentCategory}</label>
                    <div class="col-sm-9">
                        <select name="rubrik[]" class="form-control" id="rubrik" multiple="multiple" size="5" required>
                        {printCategoryOptions}
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label" for="question">{msgNewContentTheme}</label>
                    <div class="col-sm-9">
                        <textarea class="form-control" cols="37" rows="3" name="question" id="question" required {readonly}>{printQuestion}</textarea>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label" for="answer">{msgNewContentArticle}</label>
                    <div class="col-sm-9">
                        <textarea class="form-control" cols="37" rows="10" name="answer" id="answer" required></textarea>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label" for="keywords">{msgNewContentKeywords}</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" name="keywords" id="keywords">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label" for="contentlink">{msgNewContentLink}</label>
                    <div class="col-sm-9">
                        <input type="url" class="form-control" name="contentlink" id="contentlink" placeholder="http://">
                    </div>
                </div>

                {captchaFieldset}

                <div class="form-group">
                    <div class="col-sm-12 text-right">
                        <button class="btn btn-primary btn-lg" type="submit" id="submitfaq">
                            {msgNewContentSubmit}
                        </button>
                    </div>
                </div>
            </form>

            <div id="loader"></div>
            <div id="faqs"></div>

        </section>

        [enableWysiwygEditor]
        <script src="admin/assets/js/editor/tinymce.min.js?{currentTimestamp}"></script>
        <script>
            $(document).ready(function() {
                if (typeof tinyMCE !== 'undefined' && undefined !== tinyMCE) {
                    tinyMCE.init({
                        // General options
                        mode     : 'exact',
                        language : 'en',
                        elements : 'answer',
                        theme    : 'modern',
                        plugins: [
                            'advlist autolink lists link image charmap print preview hr anchor pagebreak',
                            'searchreplace wordcount visualblocks visualchars code fullscreen',
                            'insertdatetime media nonbreaking save table contextmenu directionality',
                            'emoticons template paste textcolor'
                        ],
                        relative_urls: false,
                        convert_urls: false,
                        remove_linebreaks: false,
                        use_native_selects: true,
                        paste_remove_spans: true,
                        entities : '10',
                        entity_encoding: 'raw',

                        toolbar1: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent",
                        toolbar2: "link | forecolor backcolor emoticons | print",
                        image_advtab: true,

                        // Formatting
                        style_formats: [
                            { title: 'Headers', items: [
                                { title: 'h1', block: 'h1' },
                                { title: 'h2', block: 'h2' },
                                { title: 'h3', block: 'h3' },
                                { title: 'h4', block: 'h4' },
                                { title: 'h5', block: 'h5' },
                                { title: 'h6', block: 'h6' }
                            ]},

                            { title: 'Blocks', items: [
                                { title: 'p', block: 'p' },
                                { title: 'div', block: 'div' },
                                { title: 'pre', block: 'pre' },
                                { title: 'code', block: 'code' }
                            ]},

                            { title: 'Containers', items: [
                                { title: 'blockquote', block: 'blockquote', wrapper: true },
                                { title: 'figure', block: 'figure', wrapper: true }
                            ]}
                        ],

                        visualblocks_default_state: true,
                        end_container_on_empty_block: true,
                        extended_valid_elements : "code[class],video[*],audio[*],source[*]",
                        removeformat : [
                            { selector : '*', attributes : ['style'], split : false, expand : false, deep : true }
                        ],
                        importcss_append: true,
                    });
                }
            });
        </script>
        [/enableWysiwygEditor]

        <script>
            $(document).ready(function() {
                $('#submitfaq').click(function() {
                    if (typeof tinyMCE !== 'undefined' && undefined !== tinyMCE) {
                        tinyMCE.get('answer').setContent(tinyMCE.activeEditor.getContent());
                        document.getElementById('answer').value = tinyMCE.activeEditor.getContent();
                    }
                    saveFormValues('savefaq', 'faq');
                });
                $('form#formValues').submit(function() { return false; });
            });
        </script>

