<?php

class %%CLASSNAME%% extends SmartestUserApplication{
    
    public function getNextBlogPostsForInfiniteScroll(){
        
        $s = new SmartestCmsItemSet;
        
        if($s->findBy('name', 'blog_posts_main', $this->getSite()->getId())){
            $blog_posts = $s->getMembersPagedAfterId('DEF', 5, $this->getRequestParameter('last_post_id'), $this->getSite()->getId());

            if(count($blog_posts)){
                $this->send($blog_posts, 'blog_posts');
                return;
            }
        }

        exit;

    }
    
}
