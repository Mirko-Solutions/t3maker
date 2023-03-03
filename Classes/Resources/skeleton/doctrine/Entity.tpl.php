<?= "<?php\n" ?>

namespace <?= $namespace ?>;

<?= $use_statements; ?>

class <?= $class_name."\n" ?> extends AbstractEntity
{
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
