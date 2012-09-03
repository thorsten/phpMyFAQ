        <section>

            <header>
                <h2>{msgGlossary}</h2>
            </header>

            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>{msgGlossrayItem}</th>
                        <th>{msgGlossaryDescription}</th>
                    </tr>
                </thead>
                <tfoot>
                <tr>
                    <td colspan="2">{pagination}</td>
                </tr>
                </tfoot>
                <tbody>
                    [glossaryItems]
                    <tr>
                        <td><strong>{item}</strong></td>
                        <td>{desc}</td>
                    </tr>
                    [/glossaryItems]
                </tbody>
            </table>

        </section>