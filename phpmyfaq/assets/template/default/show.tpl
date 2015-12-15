<section>
            <ul class="nav nav-tabs">
                <li><a href="#faqs" data-toggle="tab">{categoryFaqsHeader}</a></li>
                [subCategories]
                <li><a href="#subcategories" data-toggle="tab">{categorySubsHeader}</a></li>
                [/subCategories]
            </ul>

            <div class="tab-content">
                <div class="tab-pane active" id="faqs">
                    <p>{categoryDescription}</p>
                    {categoryContent}
                    <p>{categoryLevelUp}</p>
                </div>
                <div class="tab-pane" id="subcategories">
                    <p>{categoryDescription}</p>
                    {subCategoryContent}
                </div>
            </div>
        </section>