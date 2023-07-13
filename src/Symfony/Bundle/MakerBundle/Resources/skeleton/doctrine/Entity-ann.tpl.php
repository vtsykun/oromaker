<?= "<?php\n" ?>

namespace <?= $namespace ?>;

<?= $use_statements; ?>
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * <?= $class_name ?>
 *
 * @ORM\Table(name="<?= $table_name ?>")
 * @ORM\Entity(repositoryClass="<?= $repository_class_name ?>")
 * @Config(
 *   defaultValues={
 *      "security"={
 *          "type"="ACL",
 *           "group_name"="",
 *           "category"="account_management"
 *      }
 *   }
 * )
 */
class <?= $class_name."\n" ?>
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
