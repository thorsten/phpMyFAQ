<?php
/**
 * The main multi-site instances frontend.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2012-03-16
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

?>
    <header class="row">
        <div class="col-lg-12">
            <h2 class="page-header">
                <i aria-hidden="true" class="fa fa-wrench fa-fw"></i> <?php print $PMF_LANG['ad_menu_instances']; ?>
                <?php if ($user->perm->checkRight($user->getUserId(), 'addinstances') &&
                          is_writable(PMF_ROOT_DIR.DIRECTORY_SEPARATOR.'multisite')): ?>
                    <div class="pull-right">
                        <a class="btn btn-success" data-toggle="modal" href="#pmf-modal-add-instance">
                            <i aria-hidden="true" class="fa fa-plus"></i> <?php echo $PMF_LANG['ad_instance_add'] ?>
                        </a>
                    </div>
                <?php endif; ?>
            </h2>
        </div>
    </header>

    <div class="row">
        <div class="col-lg-12">
<?php
if ($user->perm->checkRight($user->getUserId(), 'editinstances')) {
    $instance = new PMF_Instance($faqConfig);
    $instanceId = PMF_Filter::filterInput(INPUT_POST, 'instance_id', FILTER_VALIDATE_INT);

    // Check, if /multisite is writeable
    if (!is_writable(PMF_ROOT_DIR.DIRECTORY_SEPARATOR.'multisite')) {
        printf(
            '<p class="alert alert-danger">%s</p>',
            $PMF_LANG['ad_instance_error_notwritable']
        );
    }

    // Update client instance
    if ('updateinstance' === $action && is_integer($instanceId)) {
        $system = new PMF_System();
        $clientInstance = new PMF_Instance_Client($faqConfig);

        // Collect data for database
        $data = [];
        $data['instance'] = PMF_Filter::filterInput(INPUT_POST, 'instance', FILTER_SANITIZE_STRING);
        $data['comment'] = PMF_Filter::filterInput(INPUT_POST, 'comment', FILTER_SANITIZE_STRING);

        if ($clientInstance->updateInstance($instanceId, $data)) {
            printf(
                '<p class="alert alert-success">%s%s</p>',
                '<a class="close" data-dismiss="alert" href="#">&times;</a>',
                $PMF_LANG['ad_config_saved']
            );
        } else {
            printf(
                '<p class="alert alert-danger">%s%s<br/>%s</p>',
                '<a class="close" data-dismiss="alert" href="#">&times;</a>',
                $PMF_LANG['ad_entryins_fail'],
                $faqConfig->getDb()->error()
            );
        }
    }
    ?>
    <table class="table">
        <thead>
        <tr>
            <th>#</th>
            <th><?php echo $PMF_LANG['ad_instance_url'] ?></th>
            <th><?php echo $PMF_LANG['ad_instance_path'] ?></th>
            <th colspan="3"><?php echo $PMF_LANG['ad_instance_name'] ?></th>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach ($instance->getAllInstances() as $site):
            $currentInstance = new PMF_Instance($faqConfig);
            $currentInstance->getInstanceById($site->id);
            $currentInstance->setId($site->id);
            ?>
        <tr id="row-instance-<?php print $site->id ?>">
            <td><?php print $site->id ?></td>
            <td><a href="<?php print $site->url.$site->instance ?>"><?php print $site->url ?></a></td>
            <td><?php print $site->instance ?></td>
            <td><?php print $site->comment ?></td>
            <td>
                <a href="?action=editinstance&instance_id=<?php print $site->id ?>" class="btn btn-info">
                    <i aria-hidden="true" class="fa fa-pencil"></i>
                </a>
            </td>
            <td>
                <?php if ($currentInstance->getConfig('isMaster') !== true): ?>
                <a href="javascript:;" id="delete-instance-<?php print $site->id ?>"
                   class="btn btn-danger pmf-instance-delete"
                   data-csrf-token="<?php echo $user->getCsrfTokenFromSession() ?>">
                    <i aria-hidden="true" class="fa fa-trash"></i>
                </a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="modal fade" id="pmf-modal-add-instance">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <a class="close" data-dismiss="modal">×</a>
                    <h3><?php echo $PMF_LANG['ad_instance_add'] ?></h3>
                </div>
                <div class="modal-body">
                    <form class="form-horizontal" action="#" method="post" accept-charset="utf-8">
                        <input type="hidden" name="csrf" id="csrf" value="<?php echo $user->getCsrfTokenFromSession() ?>">
                        <div class="form-group">
                            <label class="control-label col-lg-4">
                                <?php echo $PMF_LANG['ad_instance_url'] ?>:
                            </label>
                            <div class="col-lg-8">
                                <div class="input-group">
                                    <span class="input-group-addon">http://</span>
                                    <input class="form-control" type="text" name="url" id="url" required>
                                    <span class="input-group-addon">.<?php print $_SERVER['SERVER_NAME'] ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-lg-4">
                                <?php echo $PMF_LANG['ad_instance_path'] ?>:
                            </label>
                            <div class="col-lg-8">
                                <input class="form-control" type="text" name="instance" id="instance" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-lg-4">
                                <?php echo $PMF_LANG['ad_instance_name'] ?>:
                            </label>
                            <div class="col-lg-8">
                                <input class="form-control" type="text" name="comment" id="comment" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-lg-4" for="email">
                                <?php echo $PMF_LANG['ad_instance_email'] ?>:
                            </label>
                            <div class="col-lg-8">
                                <input class="form-control" type="email" name="email" id="email" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-lg-4">
                                <?php echo $PMF_LANG['ad_instance_admin'] ?>:
                            </label>
                            <div class="col-lg-8">
                                <input class="form-control" type="text" name="admin" id="admin" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-lg-4" for="password">
                                <?php echo $PMF_LANG['ad_instance_password'] ?>:
                            </label>
                            <div class="col-lg-8">
                                <input class="form-control" type="password" name="password" id="password" required>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <p><?php echo $PMF_LANG['ad_instance_hint'] ?></p>
                    <button class="btn btn-primary pmf-instance-add">
                        <?php echo $PMF_LANG['ad_instance_button'] ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function() {

            // Add instance
            $('.pmf-instance-add').click(function(event) {
                event.preventDefault();
                var csrf     = $('#csrf').val();
                var url      = $('#url').val();
                var instance = $('#instance').val();
                var comment  = $('#comment').val();
                var email    = $('#email').val();
                var admin    = $('#admin').val();
                var password = $('#password').val();

                $.get('index.php',
                    {
                        action: 'ajax', ajax: 'config', ajaxaction: 'add_instance', csrf: csrf, url: url,
                        instance: instance, comment: comment, email: email, admin: admin, password: password
                    },
                    function(data) {
                        if (typeof(data.added) === 'undefined') {
                            $('.table').after(
                                '<div class="alert alert-danger">Could not add instance</div>'
                            );
                        } else {
                            $('.modal').modal('hide');
                            $('.table tbody').append(
                                '<tr id="row-instance-' + data.added + '">' +
                                '<td>' + data.added + '</td>' +
                                '<td><a href="' + data.url + '">' + data.url + '</a></td>' +
                                '<td>' + instance + '</td>' +
                                '<td>' + comment + '</td>' +
                                '<td>' +
                                '<a href="?action=editinstance&instance_id=' + data.added +
                                '" class="btn btn-info"><i aria-hidden="true" class="fa fa-pencil"></i></a>' +
                                '</td>' +
                                '<td>' +
                                '<a href="javascript:;" id="delete-instance-' + data.added +
                                '" class="btn btn-danger pmf-instance-delete"><i aria-hidden="true" class="fa fa-trash"></i></a>' +
                                '</td>' +
                                '</tr>'
                            );
                        }
                    },
                    'json'
                );

            });

            // Delete instance
            $('.pmf-instance-delete').click(function(event) {
                event.preventDefault();
                var targetId = event.target.id.split('-');
                var id = targetId[2];
                var csrf = this.getAttribute('data-csrf-token');

                if (confirm('Are you sure?')) {
                    $.get('index.php',
                        { action: 'ajax', ajax: 'config', ajaxaction: 'delete_instance', instanceId: id, csrf: csrf },
                        function(data) {
                            if (typeof(data.deleted) === 'undefined') {
                                $('.table').after(
                                    '<div class="alert alert-danger">' +
                                    '<?php echo $PMF_LANG['ad_instance_error_cannotdelete'] ?> ' + data.error +
                                    '</div>'
                                );
                            } else {
                                $('#row-instance-' + id).fadeOut('slow');
                            }
                        },
                        'json'
                    );
                }
            });

        })();
    </script>

    </div>
    </div>
<?php

} else {
    print $PMF_LANG['err_NotAuth'];
}
