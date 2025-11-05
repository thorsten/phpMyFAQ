<?php

/**
 * Interface for comments repository.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 */

declare(strict_types=1);

namespace phpMyFAQ\Comment;

use phpMyFAQ\Entity\Comment;

interface CommentsRepositoryInterface
{
    /** @return array<int, object> */
    public function fetchByReferenceIdAndType(int $referenceId, string $type): array;

    public function insert(Comment $comment): bool;

    public function deleteByTypeAndId(string $type, int $commentId): bool;

    /** @return array<int, object> */
    public function countByTypeGroupedByRecordId(string $type = \phpMyFAQ\Entity\CommentType::FAQ): array;

    /** @return array<int, object> */
    public function countByCategoryForFaq(): array;

    /** @return array<int, object> */
    public function fetchAllWithCategories(string $type = \phpMyFAQ\Entity\CommentType::FAQ): array;

    public function isCommentAllowed(int $recordId, string $recordLang, string $commentType = 'faq'): bool;
}
