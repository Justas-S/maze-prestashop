<?php

use Doctrine\ORM\EntityManagerInterface;
use Maze\MazeTv\Configuration\SettingsFormBuilder;
use Maze\MazeTv\Entity\MazeStreamer;
use Maze\MazeTv\Message\PayloadBuilder;
use Maze\MazeTv\Message\PayloadSender;
use Maze\MazeTv\Repository\MazeStreamerRepository;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Validator\Constraints\Length;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

/**
 * TODO set logger object/source
 */
class MazeTv extends Module
{
    public function __construct()
    {
        $this->name = 'mazetv';
        $this->version = '1.0.1';
        $this->author = 'dev@maze.lt';
        $this->ps_versions_compliancy = array('min' => '1.7.6', 'max' => _PS_VERSION_);
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('MazeTV integration');
        $this->description = $this->trans('Integration to send information about product sales to tv.maze.lt');
    }

    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        if (!$this->registerHooks()) {
            return false;
        }

        if (!$this->createTable()) {
            return false;
        }

        return true;
    }

    private function registerHooks()
    {
        return $this->registerHook('actionManufacturerFormBuilderModifier') &&
            $this->registerHook('actionAfterCreateManufacturerFormHandler') &&
            $this->registerHook('actionAfterUpdateManufacturerFormHandler') &&
            $this->registerHook('actionObjectManufacturerDeleteAfter') &&
            $this->registerHook('actionOrderStatusPostUpdate');
    }

    private function createTable()
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'maze_streamer` (id_manufacturer int unsigned not null primary key, mazetv_key varchar(255) not null, date_add datetime not null);';
        return $entityManager->getConnection()->query($sql)->execute();
    }

    public function uninstall()
    {
        parent::uninstall();

        return Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'maze_streamer`');
    }

    public function getContent()
    {
        $output = null;
        /** @var SettingsFormBuilder $formBuilder */
        $formBuilder = $this->context->controller->getContainer()->get('maze.mazetv.configuration.settings_form_builder');
        $form = $formBuilder->build($this);

        if ($form->isSubmitAction()) {
            if ($form->validate()) {
                $form->save();
                $output .= $this->displayConfirmation($this->trans('Settings updated', [], 'Modules.Mazetv.mazetv'));
            } else if (($allErrors = $form->getValidationErrors()) !== null) {
                foreach ($allErrors as $field => $error) {
                    $output .= $this->displayError($error);
                }
            }
        }

        return $output . $form->getHtml();
    }

    public function hookActionManufacturerFormBuilderModifier($params)
    {
        /** @var FormBuilder */
        $formBuilder = $params['form_builder'];

        if (_PS_MODE_DEV_) {
            PrestaShopLogger::addLog("Form builder manufacturer id: " . $params['id']);
        }

        if ($params['id']) {
            /** @var MazeStreamer|null */
            $streamer = $this->getStreamer($params['id']);
            if ($streamer) {
                $key = $streamer->getKey();
                $params['data']['mazetv_key'] = $key;
                $formBuilder->setData($params['data']);
                if (_PS_MODE_DEV_) {
                    PrestaShopLogger::addLog("Resolved manufacturer tv key: " . ($key ? substr($key, 0, 6) . '...' : $key));
                }
            }
        }

        $formBuilder->add('mazetv_key', TextType::class, [
            'label' => $this->trans('MazeTV streamer key', [], 'Modules.Mazetv.mazetv'),
            'required'  => false,
            'constraints' => [
                new Length([
                    'max' => 64,
                    'maxMessage' => $this->trans(
                        'Admin.Notifications.Error',
                        ['%limit%' => 64]
                    ),
                ]),
            ],
        ]);
    }

    public function hookActionAfterCreateManufacturerFormHandler($params)
    {
        $id = $params['id'];
        $formData = $params['form_data'];

        if (array_key_exists('mazetv_key', $formData) && !is_null($key = $formData['mazetv_key'])) {
            $this->upsertKey($id, $key);
        }
    }

    public function hookActionAfterUpdateManufacturerFormHandler($params)
    {
        $id = $params['id'];
        $formData = $params['form_data'];

        if (array_key_exists('mazetv_key', $formData)) {
            $key = $formData['mazetv_key'];
            if (is_null($key) || $key === '') {
                $this->deleteKey($id);
            } else {
                $this->upsertKey($id, $key);
            }
        }
    }

    public function hookActionObjectManufacturerDeleteAfter($params)
    {
        $object = $params['object'];
        $this->deleteKey($object->id);
    }

    public function hookActionOrderStatusPostUpdate($params)
    {
        $orderId = $params['id_order'];
        $order = new Order($orderId);

        if (_PS_MODE_DEV_) {
            PrestaShopLogger::addLog("actionOrderStatusPostUpdate hook. Order " . $orderId . ", order state " . $order->getCurrentState());
        }

        /** @var PayloadBuilder */
        $builder = $this->context->controller->getContainer()->get('maze.mazetv.message.payload_builder');
        /** @var  PayloadSender */
        $sender = $this->context->controller->getContainer()->get('maze.mazetv.message.payload_sender');

        // If the order isn't paid for, no fuss
        if ($order->getCurrentState() !== Configuration::get('PS_OS_PAYMENT') && $order->getCurrentState() !== Configuration::get('PS_OS_OUTOFSTOCK_PAID')) {
            return;
        }

        foreach ($builder->buildMessagesForOrder($orderId) as $message) {
            $sender->send($message);
        }
    }

    private function getStreamer($manufacturerId)
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        return $entityManager->getRepository(MazeStreamer::class)->find($manufacturerId);
    }

    private function upsertKey($manufacturerId, $tvKey)
    {
        if (_PS_MODE_DEV_) {
            PrestaShopLogger::addLog("Upserting mazeztv key for: " . $manufacturerId);
        }

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        return $entityManager->transactional(function () use ($entityManager, $manufacturerId, $tvKey) {
            /** @var MazeStreamerRepository */
            $repository = $entityManager->getRepository(MazeStreamer::class);

            $streamer = $repository->find($manufacturerId);
            if ($streamer === null) {
                $streamer = new MazeStreamer();
                $streamer
                    ->setManufacturerId($manufacturerId)
                    ->setDateAdd(new DateTime());
            }
            $streamer->setKey($tvKey);

            $entityManager->persist($streamer);
        });
    }

    private function deleteKey($manufacturerId)
    {
        if (_PS_MODE_DEV_) {
            PrestaShopLogger::addLog("Deleting mazeztv key for: " . $manufacturerId);
        }

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        $streamerRef = $entityManager->getReference(MazeStreamer::class, $manufacturerId);
        $entityManager->remove($streamerRef);
        $entityManager->flush();
    }
}
