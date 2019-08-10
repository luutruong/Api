<?php

namespace Truonglv\Api\BbCode\Renderer;

use XF\Http\Request;
use Truonglv\Api\App;
use XF\Entity\Attachment;
use Truonglv\Api\XF\Str\Formatter;
use Truonglv\Api\XF\Str\EmojiFormatter;

class SimpleHtml extends \XF\BbCode\Renderer\SimpleHtml
{
    public function addTag($tag, array $config)
    {
        if (!in_array($tag, $this->getWhitelistTags(), true)) {
            unset($config['callback']);
        }

        parent::addTag($tag, $config);
    }

    public function renderTagUrl(array $children, $option, array $tag, array $options)
    {
        $options = array_replace($options, [
            'unfurl' => false,
            'allowUnfurl' => false
        ]);

        return parent::renderTagUrl($children, $option, $tag, $options);
    }

    public function renderTagAttach(array $children, $option, array $tag, array $options)
    {
        $id = intval($this->renderSubTreePlain($children));
        if ($id > 0) {
            $attachments = $options['attachments'];

            if (!empty($attachments[$id])) {
                /** @var Attachment $attachmentRef */
                $attachmentRef = $attachments[$id];
                $params = [
                    'id' => $id,
                    'attachment' => $attachmentRef,
                    'full' => $this->isFullAttachView($option),
                    'alt' => $this->getImageAltText($option) ?: ($attachmentRef ? $attachmentRef->filename : ''),
                    'attachmentViewUrl' => $this->getAttachmentViewUrl($attachmentRef)
                ];

                return $this->templater->renderTemplate('public:tapi_bb_code_tag_attach_img', $params);
            }
        }

        return parent::renderTagAttach($children, $option, $tag, $options);
    }

    public function renderTagImage(array $children, $option, array $tag, array $options)
    {
        $options['noProxy'] = true;
        $options['lightbox'] = false;

        return parent::renderTagImage($children, $option, $tag, $options);
    }

    protected function getRenderedLink($text, $url, array $options)
    {
        $html = parent::getRenderedLink($text, $url, $options);
        $linkInfo = $this->formatter->getLinkClassTarget($url);
        $html = trim($html);

        if ($linkInfo['type'] === 'internal') {
            $app = \XF::app();
            if (strpos($url, $app->options()->boardUrl) === 0) {
                $url = substr($url, strlen($app->options()->boardUrl));
            }
            $url = ltrim($url, '/');
            $request = new Request(\XF::app()->inputFilterer(), [], [], [], []);
            $match = $app->router('public')->routeToController($url, $request);

            if ($match->getController()) {
                $params = json_encode($match->getParams());
                $html = substr($html, 0, 3)
                    . ' data-tapi-route="' . htmlspecialchars($match->getController()) . '"'
                    . ' data-tapi-route-params="' . htmlspecialchars($params) . '" '
                    . substr($html, 3);
            }
        }

        return $html;
    }

    public function filterString($string, array $options)
    {
        /** @var Formatter $formatter */
        $formatter = $this->formatter;
        $formatter->setTApiDisableSmilieWithSpriteParams(true);

        /** @var EmojiFormatter $emojiFormatter */
        $emojiFormatter = $formatter->getEmojiFormatter();
        $emojiFormatter->setTApiDisableFormatToImage(true);

        return parent::filterString($string, $options);
    }

    protected function getWhitelistTags()
    {
        return [
            'attach',
            'b',
            'center',
            'code',
            'color',
            'email',
            'font',
            'i',
            'icode',
            'img',
            'indent',
            'left',
            'list',
            'plain',
            'quote',
            'right',
            's',
            'size',
            'u',
            'url',
            'user'
        ];
    }

    protected function getAttachmentViewUrl(Attachment $attachment)
    {
        /** @var \XF\Api\App $app */
        $app = \XF::app();
        $token = null;

        if ($attachment->has_thumbnail) {
            $token = App::generateTokenForViewingAttachment($attachment);
        }

        return $app->router('public')
            ->buildLink('full:attachments', $attachment, [
                'hash' => $attachment->temp_hash ?: null,
                'tapi_token' => $token
            ]);
    }
}
