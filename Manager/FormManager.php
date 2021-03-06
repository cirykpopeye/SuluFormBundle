<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\FormBundle\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\FormBundle\Entity\Form;
use Sulu\Bundle\FormBundle\Entity\FormField;
use Sulu\Bundle\FormBundle\Entity\FormTranslation;
use Sulu\Bundle\FormBundle\Entity\FormTranslationReceiver;
use Sulu\Bundle\FormBundle\Repository\FormRepository;

class FormManager
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var FormRepository
     */
    protected $formRepository;

    /**
     * EventManager constructor.
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        FormRepository $formRepository
    ) {
        $this->entityManager = $entityManager;
        $this->formRepository = $formRepository;
    }

    public function findById(int $id, ?string $locale = null): ?Form
    {
        return $this->formRepository->loadById($id, $locale);
    }

    /**
     * @param mixed[] $filters
     *
     * @return null|Form[]
     */
    public function findAll(?string $locale = null, array $filters = []): ?array
    {
        return $this->formRepository->loadAll($locale, $filters);
    }

    /**
     * @param mixed[] $filters
     */
    public function count(?string $locale = null, array $filters = []): int
    {
        return $this->formRepository->countByFilters($locale, $filters);
    }

    /**
     * @param mixed[] $data
     */
    public function save(array $data, ?string $locale = null, ?int $id = null): ?Form
    {
        $form = new Form();

        // Find exist or create new entity.
        if ($id) {
            $form = $this->findById($id, $locale);

            if (!$form) {
                return null;
            }
        }

        // Translation
        $translation = $form->getTranslation($locale, true);
        $translation->setTitle(self::getValue($data, 'title'));
        $translation->setSubject(self::getValue($data, 'subject'));
        $translation->setFromEmail(self::getValue($data, 'fromEmail'));
        $translation->setFromName(self::getValue($data, 'fromName'));
        $translation->setToEmail(self::getValue($data, 'toEmail'));
        $translation->setToName(self::getValue($data, 'toName'));
        $translation->setMailText(self::getValue($data, 'mailText'));
        $translation->setSubmitLabel(self::getValue($data, 'submitLabel'));
        $translation->setSuccessText(self::getValue($data, 'successText'));
        $translation->setSendAttachments(self::getValue($data, 'sendAttachments', false));
        $translation->setDeactivateAttachmentSave($translation->getSendAttachments() && self::getValue($data, 'deactivateAttachmentSave', false));
        $translation->setDeactivateNotifyMails(self::getValue($data, 'deactivateNotifyMails', false));
        $translation->setDeactivateCustomerMails(self::getValue($data, 'deactivateCustomerMails', false));
        $translation->setReplyTo(self::getValue($data, 'replyTo', false));
        $translation->setChanged(new \DateTime());

        // Add Translation to Form.
        if (!$translation->getId()) {
            $translation->setForm($form);
            $form->addTranslation($translation);
        }

        // Set Default Locale.
        if (!$form->getId()) {
            $form->setDefaultLocale($locale);
        }

        // Update field of form and the receivers.
        $this->updateFields($data, $form, $locale);
        $this->updateReceivers($data, $translation);

        $this->entityManager->persist($form);
        $this->entityManager->flush();

        if (!$id) {
            // To avoid lazy load of sub entities in the serializer reload whole object with sub entities from db
            // remove this when you don`t join anything in `findById`.
            $form = $this->findById($form->getId(), $locale);
        }

        return $form;
    }

    public function delete(int $id, ?string $locale = null): ?Form
    {
        $object = $this->findById($id, $locale);

        if (!$object) {
            return null;
        }

        $this->entityManager->remove($object);
        $this->entityManager->flush();

        return $object;
    }

    /**
     * @param mixed[] $data
     */
    public function updateReceivers(array $data, FormTranslation $translation): void
    {
        $receiversRepository = $this->entityManager->getRepository(FormTranslationReceiver::class);
        $receiverDatas = self::getValue($data, 'receivers', []);

        // Remove old receivers.
        $oldReceivers = $receiversRepository->findBy(['formTranslation' => $translation]);
        /** @var FormTranslationReceiver $oldReceiver */
        foreach ($oldReceivers as $oldReceiver) {
            $translation->removeReceiver($oldReceiver);
            $this->entityManager->remove($oldReceiver);
        }

        $receivers = [];
        foreach ($receiverDatas as $receiverData) {
            $receiver = new FormTranslationReceiver();
            $receiver->setType($receiverData['type']);
            $receiver->setEmail($receiverData['email']);
            if (!array_key_exists('name', $receiverData)) {
                $receiverData['name'] = null;
            }
            $receiver->setName($receiverData['name']);
            $receiver->setFormTranslation($translation);

            $receivers[] = $receiver;
            $this->entityManager->persist($receiver);
            $translation->addReceiver($receiver);
        }
    }

    /**
     * Updates the contained fields in the form.
     *
     * @param mixed[] $data
     */
    protected function updateFields(array $data, Form $form, string $locale): void
    {
        $reservedKeys = array_column(self::getValue($data, 'fields', []), 'key');

        $counter = 0;

        foreach (self::getValue($data, 'fields', []) as $fieldData) {
            ++$counter;
            $fieldType = self::getValue($fieldData, 'type');
            $fieldKey = self::getValue($fieldData, 'key');
            $field = $form->getField($fieldKey);
            $uniqueKey = $this->getUniqueKey($fieldType, $reservedKeys);

            if (!$field) {
                $field = $form->getField($uniqueKey);
            }

            if (!$field) {
                $field = new FormField();
                $field->setKey($uniqueKey);
                $reservedKeys[] = $uniqueKey;
            } elseif ($field->getType() !== $fieldType || !$field->getKey()) {
                $field->setKey($uniqueKey);
                $reservedKeys[] = $uniqueKey;
            }

            $field->setOrder($counter);
            $field->setType($fieldType);
            $field->setWidth(self::getValue($fieldData, 'width', 'full'));
            $field->setRequired(self::getValue($fieldData, 'required', false));

            // Field Translation
            $fieldTranslation = $field->getTranslation($locale, true);
            $fieldTranslation->setTitle(self::getValue($fieldData, 'title'));
            $fieldTranslation->setPlaceholder(self::getValue($fieldData, 'placeholder'));
            $fieldTranslation->setHelpText(self::getValue($fieldData, 'helpText'));
            $fieldTranslation->setDefaultValue(self::getValue($fieldData, 'defaultValue'));
            $fieldTranslation->setShortTitle(self::getValue($fieldData, 'shortTitle'));
            $fieldTranslation->setOptions(self::getValue($fieldData, 'options'));

            // Add Translation to Field
            if (!$fieldTranslation->getId()) {
                $fieldTranslation->setField($field);
                $field->addTranslation($fieldTranslation);
            }

            // Add Field to Form
            if (!$field->getId()) {
                $field->setDefaultLocale($locale);
                $field->setForm($form);
                $form->addField($field);
            }
        }

        // Remove Fields
        foreach ($form->getFieldsNotInArray($reservedKeys) as $deletedField) {
            $form->removeField($deletedField);
            $this->entityManager->remove($deletedField);
        }
    }

    /**
     * @param mixed[] $data
     * @param null|mixed $default
     *
     * @return mixed
     */
    protected static function getValue(array $data, string $value, $default = null, ?string $type = null)
    {
        if (isset($data[$value])) {
            if ('date' === $type) {
                if (!$data[$value]) {
                    return $default;
                }

                return new \DateTime($data[$value]);
            }

            return $data[$value];
        }

        return $default;
    }

    /**
     * @param string[] $keys
     */
    protected function getUniqueKey(string $type, array $keys, int $counter = 0): string
    {
        $name = $type;

        if ($counter) {
            $name .= $counter;
        }

        if (!in_array($name, $keys)) {
            return $name;
        }

        return $this->getUniqueKey($type, $keys, ++$counter);
    }
}
