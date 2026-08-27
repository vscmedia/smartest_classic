<?php

class %CLASSNAME% extends SmartestUserApplication{
    
    public function getNextBlogPostsForInfiniteScroll(){
        
        $s = new SmartestCmsItemSet;
        
        if($s->findBy('name', 'blog_posts_main', $this->getSite()->getId())){
            $this->send($s->getMembersPagedAfterId('DEF', 5, $this->getRequestParameter('last_post_id')), 'blog_posts');
        }else{
            $this->send(array(), 'blog_posts');
        }
        
        
    }
    
}
