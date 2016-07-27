<?php
use App\ContentAccessControl;
use Minhbang\Article\Article;

class StatusManagerTest extends TestCase
{
    public function testMethods()
    {
        $status = new ContentAccessControl(Article::class);

        $this->assertTrue($status->editingValue() === ContentAccessControl::STATUS_EDITING);
        $this->assertTrue($status->publishedValue() === [ContentAccessControl::STATUS_PUBLISHED]);

        $this->assertTrue($status->has(ContentAccessControl::STATUS_EDITING));
        $this->assertFalse($status->has(100));

        $this->assertTrue($status->get('title', ContentAccessControl::STATUS_EDITING) === trans('status.editing'));
        $this->assertTrue($status->get('title1', ContentAccessControl::STATUS_EDITING, false) === false);

        $this->assertTrue($status->pluck() === [
                ContentAccessControl::STATUS_EDITING   => trans('status.editing'),
                ContentAccessControl::STATUS_REVIEWING => trans('status.reviewing'),
                ContentAccessControl::STATUS_REFUSED   => trans('status.refused'),
                ContentAccessControl::STATUS_PUBLISHED => trans('status.published'),
            ]
        );
        $this->assertTrue($status->pluck('title', false) === [
                trans('status.editing'),
                trans('status.reviewing'),
                trans('status.refused'),
                trans('status.published'),
            ]
        );

        
        
    }
}