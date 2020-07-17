<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Tests\Generator\Builder\Om;

use Propel\Runtime\Collection\ObjectCollection;
use Propel\Generator\Util\QuickBuilder;
use Propel\Tests\TestCase;

/**
 * Tests for More relations
 *
 * @author MArc J. Schmidt
 * @group database
 */
class GeneratedObjectMoreRelationTest extends TestCase
{
    /**
     * Setup schema und some default data
     */
    public function setUp(): void
    {
        parent::setUp();

        if (!class_exists('MoreRelationTest\Page')) {
            $schema = <<<EOF
<database name="more_relation_test" namespace="MoreRelationTest" activeRecord="true">

    <entity name="more_relation_test_page" phpName="Page">
        <field name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <field name="title" type="VARCHAR" size="100" primaryString="true" />
    </entity>

    <entity name="more_relation_test_content" phpName="Content">
        <field name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <field name="title" type="VARCHAR" size="100" />
        <field name="content" type="LONGVARCHAR" required="false" />
        <field name="pageId" type="INTEGER" required="false" />
        <relation target="Page" onDelete="cascade">
          <reference local="pageId" foreign="id"/>
        </relation>
    </entity>

    <entity name="more_relation_test_comment" phpName="Comment">
        <field name="userId" required="true" primaryKey="true" type="INTEGER" />
        <field name="pageId" required="true" primaryKey="true" type="INTEGER" />
        <field name="comment" type="VARCHAR" size="100" />
        <relation target="Page" onDelete="cascade">
          <reference local="pageId" foreign="id"/>
        </relation>
    </entity>

    <entity name="more_relation_test_content_comment" phpName="ContentComment">
        <field name="id" required="true" autoIncrement="true" primaryKey="true" type="INTEGER" />
        <field name="contentId" type="INTEGER" />
        <field name="comment" type="VARCHAR" size="100" />
        <relation target="Content" onDelete="setnull">
          <reference local="contentId" foreign="id"/>
        </relation>
    </entity>

</database>
EOF;

            $builder = new QuickBuilder();
            $builder->setSchema($schema);
            $builder->build();
        }

        \MoreRelationTest\ContentCommentQuery::create()->doDeleteAll();
        \MoreRelationTest\ContentQuery::create()->doDeleteAll();
        \MoreRelationTest\CommentQuery::create()->doDeleteAll();
        \MoreRelationTest\PageQuery::create()->doDeleteAll();

        for ($i=1;$i<=2;$i++) {
            $page = new \MoreRelationTest\Page();
            $page->setTitle('Page '.$i);

            for ($j=1;$j<=3;$j++) {
                $content = new \MoreRelationTest\Content();
                $content->setTitle('Content '.$j);
                $content->setContent(str_repeat('Content', $j));
                $page->addContent($content);

                $comment = new \MoreRelationTest\Comment();
                $comment->setUserId($j);
                $comment->setComment(str_repeat('Comment', $j));
                $page->addComment($comment);

                $comment = new \MoreRelationTest\ContentComment();
                $comment->setComment(str_repeat('Comment-'.$j.', ', $j));
                $content->addContentComment($comment);
            }

            $page->save();
        }
    }

    public function testRefRelation()
    {
        /** @var $page \MoreRelationTest\Page */
        $page = \MoreRelationTest\PageQuery::create()->findOne();
        $comments = $page->getComments();
        $count = count($comments);

        $comment = new \MoreRelationTest\Comment();
        $comment->setComment('Comment 1');
        $comment->setUserId(123);
        $comment->setPage($page);

        $this->assertCount($count + 1, $comments);
        $this->assertCount($count + 1, $page->getComments());

        //remove
        $page->removeComment($comment);
        $this->assertCount($count, $comments);
        $this->assertCount($count, $page->getComments());
    }

    public function testDuplicate()
    {
        $page = \MoreRelationTest\PageQuery::create()->findOne();
        $pageComment = \MoreRelationTest\CommentQuery::create()->filterByPage($page)->findOne();
        $currentCount = count($page->getComments());

        $this->assertSame(
            spl_object_hash($page->getComments()[0]),
            spl_object_hash($pageComment),
            'lazy loading of one-to-many adds the reference/proxy to first level cache, so a query returns that object'
        );

        /** @var $newPageObject \MoreRelationTest\Page */
        $newPageObject = \MoreRelationTest\PageQuery::create()->findOne(); //resets the cached comments through getComments()
        $this->assertCount($currentCount, $newPageObject->getComments(), 'same count as before');
        $newPageObject->addComment($pageComment);
        $this->assertCount($currentCount, $newPageObject->getComments(), 'same count as before, because already in the list');
    }

    /**
     * Composite PK deletion of a 1-to-n relation through set<RelationName>() and remove<RelationName>()
     * where the PK is at the same time a FK.
     */
    public function testCommentsDeletion()
    {
        $commentCollection = new ObjectCollection();
        $commentCollection->setModel('MoreRelationTest\\Comment');

        $comment = new \MoreRelationTest\Comment();
        $comment->setComment('I should be alone :-(');
        $comment->setUserId(123);

        $commentCollection[] = $comment;

        $page = \MoreRelationTest\PageQuery::create()->findOne();
        $id = $page->getId();

        $count = \MoreRelationTest\CommentQuery::create()->filterByPageId($id)->count();
        $this->assertEquals(3, $count, 'We created for each page 3 comments.');

        $page->setComments($commentCollection);
        $page->save();

        $count = \MoreRelationTest\CommentQuery::create()->filterByPageId($id)->count();
        $this->assertEquals(1, $count, 'We assigned a collection of only one item.');

        $count = \MoreRelationTest\CommentQuery::create()->filterByPageId(null)->count();
        $this->assertEquals(0, $count, 'There should be no unassigned comment.');

        $page->removeComment($comment);
        $this->assertNull($comment->getPage(), 'comment is not linked to page anymore.');

        $page->save();

        $count = \MoreRelationTest\CommentQuery::create()->filterByPageId($id)->count();
        $this->assertEquals(0, $count, 'no comments are linked with the page');

        $count = \MoreRelationTest\CommentQuery::create()->filterByPageId(null)->count();
        $this->assertEquals(0, $count, 'we have now no unassigned comments');
    }

    /**
     * Deletion of a 1-to-n relation through set<RelationName>()
     * with onDelete=setnull
     * @group test
     */
    public function testContentCommentDeletion()
    {
        $commentCollection = new ObjectCollection();
        $commentCollection->setModel('MoreRelationTest\\ContentComment');

        $comment = new \MoreRelationTest\ContentComment();
        $comment->setComment('I\'m Mario');
        $commentCollection[] = $comment;

        $comment2 = new \MoreRelationTest\ContentComment();
        $comment2->setComment('I\'m Mario\'s friend');
        $commentCollection[] = $comment2;

        $content = \MoreRelationTest\ContentQuery::create()->findOne();
        $oldContentComments = $content->getContentComments();
        $id = $content->getId();

        $count = \MoreRelationTest\ContentCommentQuery::create()->filterByContentId($id)->count();
        $this->assertEquals(1, $count, 'We created for each page 1 comments.');

        $content->setContentComments($commentCollection);
        $content->save();

        unset($content);

        $count = \MoreRelationTest\ContentCommentQuery::create()->filterByContentId($id)->count();
        $this->assertEquals(2, $count, 'We assigned a collection of two items.');

        $count = \MoreRelationTest\ContentCommentQuery::create()->filterByContentId(null)->count();
        $this->assertEquals(1, $count, 'There should be one unassigned contentComment.');
    }

    /**
     * Basic deletion of a 1-to-n relation through set<RelationName>().
     */
    public function testContentsDeletion()
    {
        $contentCollection = new ObjectCollection();
        $contentCollection->setModel('MoreRelationTest\\Content');

        $content = new \MoreRelationTest\Content();
        $content->setTitle('I should be alone :-(');

        $contentCollection[] = $content;

        /** @var \MoreRelationTest\Page $page */
        $page = \MoreRelationTest\PageQuery::create()->findOne();
        $id = $page->getId();

        $count = \MoreRelationTest\ContentQuery::create()->filterByPageId($id)->count();
        $this->assertEquals(3, $count, 'We created for each page 3 contents.');

        $page->setContents($contentCollection);
        $page->save();

        unset($page);

        $count = \MoreRelationTest\ContentQuery::create()->filterByPageId($id)->count();
        $this->assertEquals(1, $count, 'We assigned a collection of only one item.');
    }

    public function testOnDeleteCascadeNotRequired()
    {
        \MoreRelationTest\PageQuery::create()->doDeleteAll();
        \MoreRelationTest\ContentQuery::create()->doDeleteAll();

        $page = new \MoreRelationTest\Page();
        $page->setTitle('Some important Page');

        $content = new \MoreRelationTest\Content();
        $content->setTitle('Content');

        $page->addContent($content);
        $page->save();

        $this->assertEquals(1, \MoreRelationTest\ContentQuery::create()->count());

        $page->removeContent($content);
        $page->save();

        //since the relation has required="false" (one of its local fields)
        //we do not remove orphans.
        $this->assertEquals(1, \MoreRelationTest\ContentQuery::create()->count());
    }
}
