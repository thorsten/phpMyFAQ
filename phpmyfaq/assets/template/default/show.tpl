<section>
            <header>
                <h2>{categoryHeader}</h2>
            </header>
            <p>{categoryDescription}</p>

            <ul class="nav nav-tabs">
                <li><a href="#faqs" data-toggle="tab"><h4>{categoryFaqsHeader}</h4></a></li>
                <li><a href="#subcategories" data-toggle="tab"><h4>{categorySubsHeader}</h4></a></li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane active" id="faqs">
                    {categoryContent}
                    <p>{categoryLevelUp}</p>
                </div>
                <div class="tab-pane" id="subcategories">
                {subCategoryContent}
                </div>
            </div>
        </section>