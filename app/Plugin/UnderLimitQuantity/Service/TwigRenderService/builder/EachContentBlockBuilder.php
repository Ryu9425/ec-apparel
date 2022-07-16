<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/03/22
 */

namespace Plugin\UnderLimitQuantity\Service\TwigRenderService\builder;


use Plugin\UnderLimitQuantity\Service\TwigRenderService\builder\base\EachContentBlockBuilderBase;
use Plugin\UnderLimitQuantity\Service\TwigRenderService\Content\ContentBlock;

class EachContentBlockBuilder extends EachContentBlockBuilderBase
{

    /**
     * @param $templatePath
     * @return $this
     */
    public function setTemplate($templatePath)
    {
        $this->contentBlock->setInsertTemplate($templatePath, true);
        return $this;
    }

    public function build()
    {
        $this->contentBlock->setInsertMode(ContentBlock::INSERT_EACH);
        return $this->contentBlock;
    }
}
