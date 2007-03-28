    <h2>{msgInstantResponse}</h2>

    <script type="text/javascript" src="inc/js/prototype.js"></script>

    <form id="instantform" action="?action=instantresponse" method="post">
        <fieldset>
            <legend>{msgSearchWord}</legend>

            <input class="inputfield" id="instantfield" type="text" name="search" value="{searchString}" />

        </fieldset>
    </form>
    <script type="text/javascript">
    //<![CDATA[
        // Delay over author's typing before starting any Instant Response attempt
        var delay = 200; // msec
        // Minimum number of chars to start any Instant Response attempt
        var minLen = 1;

        // Private variables
        var _lock = false;
        var _searchField = '';

        function setDelay()
        {
            _lock = true;
            _searchField = $F('instantfield');
            setTimeout('_lock = false;', delay);
        }

        function getInstantResponse()
        {
            if (
                   (!_lock)
                && ($F('instantfield').length >= minLen)
                && ($F('instantfield') != _searchField)
                ) {
                _searchField = $F('instantfield');
                new Ajax.Updater(
                    'instantresponse',
                    'ajaxresponse.php',
                    {
                        asynchronous:true,
                        parameters:Form.serialize('instantform'),
                        onComplete:setDelay
                    }
                );
            }
        }

        Event.observe(window, 'load', function()
            {
                Event.observe('instantform', 'keyup', getInstantResponse, false);
            }
        );
    //]]>
    </script>

    <div id="instantresponse">
    {printInstantResponse}
    </div>