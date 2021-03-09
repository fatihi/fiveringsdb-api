<?php

namespace AppBundle\Form\Type;

use AppBundle\Entity\Card;
use AppBundle\Entity\Deck;
use AppBundle\Search\DeckSearch;
use AppBundle\Service\DeckSearchService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Description of DeckSearchType
 *
 * @author Alsciende <alsciende@icloud.com>
 */
class DeckSearchType extends AbstractType
{
    /** @var DeckSearchService $deckSearchService */
    private $deckSearchService;

    public function __construct (DeckSearchService $deckSearchService)
    {
        $this->deckSearchService = $deckSearchService;
    }

    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('sort', ChoiceType::class, [
                'choices' => $this->deckSearchService->getSupported(),
            ])
            ->add('since', DateType::class, [
                'widget' => 'single_text',
                'format' => DateType::HTML5_FORMAT,
            ])
            ->add('primaryClan', ChoiceType::class, [
                'choices' => Card::CLANS,
            ])
            ->add('secondaryClan', ChoiceType::class, [
                'choices' => Card::CLANS,
            ])
            ->add('format', ChoiceType::class, [
                'choices' => Deck::FORMATS,
            ])
            ->add('featured', CheckboxType::class)
            ->add('cards', EntityType::class, [
                'class' => Card::class,
                'multiple' => true,
            ])
            ->add('page', IntegerType::class)
            ->add('limit', IntegerType::class);
    }

    public function configureOptions (OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'         => DeckSearch::class,
                'allow_extra_fields' => true,
            ]
        );
    }
}