<?php

/**
 * Helper class for phpMyFAQ categories.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-04-01
 */

declare(strict_types=1);

namespace phpMyFAQ\Helper;

use phpMyFAQ\Date;
use phpMyFAQ\Entity\Comment;
use phpMyFAQ\Mail;
use phpMyFAQ\Service\Gravatar;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\Utils;

class CommentHelper extends AbstractHelper
{
    /**
     * Returns all user comments (HTML formatted) from a record by type.
     *
     * @param Comment[] $comments
     */
    #[\Deprecated(message: 'Rewrite this method to use Twig, will be removed in v4.1')]
    public function getComments(array $comments): string
    {
        $date = new Date($this->configuration);
        $mail = new Mail($this->configuration);
        $gravatar = new Gravatar();

        $output = '';
        foreach ($comments as $comment) {
            $output .= '<div class="row mt-2 mb-2">';
            $output .= '  <div class="col-sm-1">';
            $output .= '    <div class="thumbnail">';
            $output .= $gravatar->getImage($comment->getEmail(), ['class' => 'img-thumbnail']);
            $output .= '   </div>';
            $output .= '  </div>';

            $output .= '  <div class="col-sm-11">';
            $output .= '    <div class="card">';
            $output .= '     <div class="card-header card-header-comments">';
            $output .= sprintf(
                '<strong><a href="mailto:%s">%s</a></strong>',
                $mail->safeEmail($comment->getEmail()),
                Strings::htmlentities($comment->getUsername()),
            );
            $output .= sprintf(' <span class="text-muted">(%s)</span>', $date->format($comment->getDate()));
            $output .= '     </div>';
            $output .= sprintf('<div class="card-body">%s</div>', $this->showShortComment(
                $comment->getId(),
                $comment->getComment(),
            ));
            $output .= '   </div>';
            $output .= '  </div>';
            $output .= '</div>';
        }

        return $output;
    }

    /**
     * Adds some fancy HTML if a comment is too long.
     */
    private function showShortComment(int $commentId, string $comment): string
    {
        $words = explode(' ', $comment);
        $numWords = 0;

        $comment = '';
        foreach ($words as $word) {
            $comment .= $word . ' ';
            if (15 === $numWords) {
                $comment .= sprintf(
                    '<span class="comment-dots-%d">&hellip; </span><a href="#" data-comment-id="%d" '
                    . 'class="pmf-comments-show-more comment-show-more-%d">%s</a><span class="comment-more-%d d-none">',
                    $commentId,
                    $commentId,
                    $commentId,
                    Translation::get(key: 'msgShowMore'),
                    $commentId,
                );
            }

            ++$numWords;
        }

        return Utils::parseUrl($comment) . '</span>';
    }
}
