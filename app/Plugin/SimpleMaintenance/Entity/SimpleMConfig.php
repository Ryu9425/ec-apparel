<?php
namespace Plugin\SimpleMaintenance\Entity;


use Doctrine\ORM\Mapping as ORM;

/**
 * SimpleMConfig
 *
 * @ORM\Table(name="plg_simple_maintenance_config")
 * @ORM\Entity(repositoryClass="Plugin\SimpleMaintenance\Repository\SimpleMConfigRepository")
 */
class SimpleMConfig
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="smallint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var boolean
     *
     * @ORM\Column(name="mente_mode", type="boolean", options={"default":false})
     */
    private $mente_mode;

    /**
     * @var boolean
     *
     * @ORM\Column(name="admin_close_flg", type="boolean", options={"default":false})
     */
    private $admin_close_flg;

    /**
     * @var string|null
     *
     * @ORM\Column(name="page_html", type="text", nullable=true)
     */
    private $page_html;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return bool
     */
    public function isMenteMode(): bool
    {
        return $this->mente_mode;
    }

    /**
     * @param bool $mente_mode
     * @return SimpleMConfig
     */
    public function setMenteMode(bool $mente_mode): SimpleMConfig
    {
        $this->mente_mode = $mente_mode;
        return $this;
    }

    /**
     * @return bool
     */
    public function isAdminCloseFlg(): bool
    {
        return $this->admin_close_flg;
    }

    /**
     * @param bool $admin_close_flg
     * @return SimpleMConfig
     */
    public function setAdminCloseFlg(bool $admin_close_flg): SimpleMConfig
    {
        $this->admin_close_flg = $admin_close_flg;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getPageHtml(): ?string
    {
        return $this->page_html;
    }

    /**
     * @param null|string $page_html
     * @return SimpleMConfig
     */
    public function setPageHtml(?string $page_html): SimpleMConfig
    {
        $this->page_html = $page_html;
        return $this;
    }

}
