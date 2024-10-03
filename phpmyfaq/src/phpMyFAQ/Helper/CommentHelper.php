<?php

namespace phpMyFAQ\Helper;

use phpMyFAQ\Date;
use phpMyFAQ\Entity\Comment;
use phpMyFAQ\Mail;
use phpMyFAQ\Services\Gravatar;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\Utils;

class CommentHelper extends AbstractHelper
{
    /**
     * Returns all user comments (HTML formatted) from a record by type.
     *
     * @param Comment[] $comments
     * @deprecated Rewrite this method to use Twig
     */
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
                Strings::htmlentities($comment->getUsername())
            );
            $output .= sprintf(' <span class="text-muted">(%s)</span>', $date->format($comment->getDate()));
            $output .= '     </div>';
            $output .= sprintf(
                '<div class="card-body">%s</div>',
                $this->showShortComment($comment->getId(), $comment->getComment())
            );
            $output .= '   </div>';
            $output .= '  </div>';
            $output .= '</div>';
        }

        return $output;
    }

    /**
     * Adds some fancy HTML if a comment is too long.
     */
    private function showShortComment(int $id, string $comment): string
    {
        $words = explode(' ', nl2br($comment));
        $numWords = 0;

        $comment = '';
        foreach ($words as $word) {
            $comment .= Strings::htmlentities($word . ' ');
            if (15 === $numWords) {
                $comment .= '<span class="comment-dots-' . $id . '">&hellip; </span>' .
                    '<a href="#" data-comment-id="' . $id . '" class="pmf-comments-show-more comment-show-more-' . $id .
                    '">' . Translation::get('msgShowMore') . '</a>' .
                    '<span class="comment-more-' . $id . ' d-none">';
            }

            ++$numWords;
        }

        // Convert URLs to HTML anchors
        return Utils::parseUrl($comment) . '</span>';
    }
}
