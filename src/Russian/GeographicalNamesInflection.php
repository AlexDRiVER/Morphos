<?php
namespace morphos\Russian;

use morphos\S;

/**
 * Rules are from: https://ru.wikipedia.org/wiki/%D0%A1%D0%BA%D0%BB%D0%BE%D0%BD%D0%B5%D0%BD%D0%B8%D0%B5_%D0%B3%D0%B5%D0%BE%D0%B3%D1%80%D0%B0%D1%84%D0%B8%D1%87%D0%B5%D1%81%D0%BA%D0%B8%D1%85_%D0%BD%D0%B0%D0%B7%D0%B2%D0%B0%D0%BD%D0%B8%D0%B9_%D0%B2_%D1%80%D1%83%D1%81%D1%81%D0%BA%D0%BE%D0%BC_%D1%8F%D0%B7%D1%8B%D0%BA%D0%B5
 */
class GeographicalNamesInflection extends \morphos\BaseInflection implements Cases
{
    use RussianLanguage, CasesHelper;

    protected static $abbreviations = array(
        'сша',
        'оаэ',
        'ссср',
        'юар',
    );

    /**
     * Проверяет, склоняемо ли название
     * @param string $name Название
     * @return bool
     */
    public static function isMutable($name)
    {
        $name = S::lower($name);

        // // ends with 'ы' or 'и': plural form
        // if (in_array(S::slice($name, -1), array('и', 'ы')))
        //     return false;

        if (in_array($name, self::$abbreviations)) {
            return false;
        }

        // N край
        if (S::slice($name, -5) == ' край') {
            return static::isMutable(S::slice($name, 0, -5));
        }

        // N область
        if (S::slice($name, -8) == ' область') {
            return true;
        }

        // город N
        if (S::slice($name, 0, 6) == 'город ') {
            return true;
        }

        // ends with 'е' or 'о', but not with 'ово/ёво/ево/ино/ыно'
        if (in_array(S::slice($name, -1), array('е', 'о')) && !in_array(S::slice($name, -3, -1), array('ов', 'ёв', 'ев', 'ин', 'ын'))) {
            return false;
        }
        return true;
    }

    /**
     * Получение всех форм названия
     * @param string $name
     * @return array
     */
    public static function getCases($name)
    {
        $name = S::lower($name);

        // N край
        if (S::slice($name, -5) == ' край') {
            return self::composeCasesFromWords([static::getCases(S::slice($name, 0, -5)), NounDeclension::getCases('край')]);
        }

        // N область
        if (S::slice($name, -8) == ' область') {
            return self::composeCasesFromWords([static::getCases(S::slice($name, 0, -8)), NounDeclension::getCases('область')]);
        }

        // город N
        if (S::slice($name, 0, 6) == 'город ') {
            return self::composeCasesFromWords([
                NounDeclension::getCases('город'),
                array_fill_keys(self::getAllCases(), S::name(S::slice($name, -6)))
            ]);
        }

        // Сложное название города из нескольких слов через пробел.
        // Нижний Новгород, Набережные Челны
        if (strpos($name, ' ') !== false) {
            $parts = explode(' ', $name);
            $result = array();
            foreach ($parts as $i => $part) {
                $result[$i] = static::getCases($part);
            }
            return self::composeCasesFromWords($result);
        }

        // Сложное название города из нескольких слов через тире.
        // Ростов-на-Дону, Переславль-Залесский
        if (strpos($name, '-') !== false) {
            // грязный хак, но подобных случаев как с Ростовом-на-Дону нету
            if ($name == 'ростов-на-дону') {
                return [
                    self::IMENIT => 'Ростов-на-Дону',
                    self::RODIT => 'Ростова-на-Дону',
                    self::DAT => 'Ростову-на-Дону',
                    self::VINIT => 'Ростов-на-Дону',
                    self::TVORIT => 'Ростовом-на-Дону',
                    self::PREDLOJ => 'Ростове-на-Дону'
                ];
            } else {
                $parts = explode('-', $name);
                $result = array(
                    array_fill_keys([self::IMENIT, self::RODIT, self::DAT, self::VINIT, self::TVORIT, self::PREDLOJ], S::name($parts[0])),
                );
                for ($i = 1, $total_parts = count($parts); $i < $total_parts; $i++)
                    $result[] = static::getCases($parts[$i]); // вторая часть, склоняемая
            }
            return self::composeCasesFromWords($result, '-');
        }

        if (!in_array($name, self::$abbreviations)) {
            if (S::slice($name, -2) == 'ий') {
                // Нижний, Русский
                $prefix = S::name(S::slice($name, 0, -2));
                return array(
                    self::IMENIT => $prefix.'ий',
                    self::RODIT => $prefix.(self::isVelarConsonant(S::slice($name, -3, -2)) ? 'ого' : 'его'),
                    self::DAT => $prefix.(self::isVelarConsonant(S::slice($name, -3, -2)) ? 'ому' : 'ему'),
                    self::VINIT => $prefix.'ий',
                    self::TVORIT => $prefix.'им',
                    self::PREDLOJ => $prefix.(self::chooseEndingBySonority($prefix, 'ем', 'ом')),
                );
            } else if (S::slice($name, -2) == 'ая') {
                // Ростовская
                $prefix = S::name(S::slice($name, 0, -2));
                return array(
                    self::IMENIT => $prefix.'ая',
                    self::RODIT => $prefix.'ой',
                    self::DAT => $prefix.'ой',
                    self::VINIT => $prefix.'ую',
                    self::TVORIT => $prefix.'ой',
                    self::PREDLOJ => $prefix.'ой',
                );
            } else if (S::slice($name, -2) == 'ый') {
                // Грозный, Благодарный
                $prefix = S::name(S::slice($name, 0, -2));
                return array(
                    self::IMENIT => $prefix.'ый',
                    self::RODIT => $prefix.'ого',
                    self::DAT => $prefix.'ому',
                    self::VINIT => $prefix.'ый',
                    self::TVORIT => $prefix.'ым',
                    self::PREDLOJ => $prefix.'ом',
                );
            } elseif (S::slice($name, -1) == 'а') {
                // Москва, Рига
                $prefix = S::name(S::slice($name, 0, -1));
                return array(
                    self::IMENIT => $prefix.'а',
                    self::RODIT => $prefix.(self::isVelarConsonant(S::slice($name, -2, -1)) ? 'и' : 'ы'),
                    self::DAT => $prefix.'е',
                    self::VINIT => $prefix.'у',
                    self::TVORIT => $prefix.'ой',
                    self::PREDLOJ => $prefix.'е',
                );
            } elseif (S::slice($name, -1) == 'я') {
                // Азия
                $prefix = S::name(S::slice($name, 0, -1));
                return array(
                    self::IMENIT => S::name($name),
                    self::RODIT => $prefix.'и',
                    self::DAT => $prefix.'и',
                    self::VINIT => $prefix.'ю',
                    self::TVORIT => $prefix.'ей',
                    self::PREDLOJ => $prefix.'и',
                );
            } elseif (S::slice($name, -1) == 'й') {
                // Ишимбай
                $prefix = S::name(S::slice($name, 0, -1));
                return array(
                    self::IMENIT => $prefix.'й',
                    self::RODIT => $prefix.'я',
                    self::DAT => $prefix.'ю',
                    self::VINIT => $prefix.'й',
                    self::TVORIT => $prefix.'ем',
                    self::PREDLOJ => $prefix.'е',
                );
            } elseif (self::isConsonant(S::slice($name, -1)) && S::slice($name, -2) != 'ов') {
                // Париж, Валаам, Киев
                $prefix = S::name($name);
                return array(
                    self::IMENIT => $prefix,
                    self::RODIT => $prefix.'а',
                    self::DAT => $prefix.'у',
                    self::VINIT => $prefix,
                    self::TVORIT => $prefix.(self::isVelarConsonant(S::slice($name, -2, -1)) ? 'ем' : 'ом'),
                    self::PREDLOJ => $prefix.'е',
                );
            } elseif (S::slice($name, -2) == 'ль') {
                // Ставрополь, Ярославль
                $prefix = S::name(S::slice($name, 0, -1));
                return array(
                    self::IMENIT => $prefix.'ь',
                    self::RODIT => $prefix.'я',
                    self::DAT => $prefix.'ю',
                    self::VINIT => $prefix.'ь',
                    self::TVORIT => $prefix.'ем',
                    self::PREDLOJ => $prefix.'е',
                );
            } elseif (S::slice($name, -2) == 'рь') {
                // Тверь
                $prefix = S::name(S::slice($name, 0, -1));
                return array(
                    self::IMENIT => $prefix.'ь',
                    self::RODIT => $prefix.'и',
                    self::DAT => $prefix.'и',
                    self::VINIT => $prefix.'ь',
                    self::TVORIT => $prefix.'ью',
                    self::PREDLOJ => $prefix.'и',
                );
            } elseif (S::slice($name, -2) == 'ки') {
                // Березники, Ессентуки
                $prefix = S::name(S::slice($name, 0, -1));
                return array(
                    self::IMENIT => $prefix.'и',
                    self::RODIT => $prefix.'ов',
                    self::DAT => $prefix.'ам',
                    self::VINIT => $prefix.'и',
                    self::TVORIT => $prefix.'ами',
                    self::PREDLOJ => $prefix.'ах',
                );
            } elseif (S::slice($name, -2) == 'мь') {
                // Пермь, Кемь
                $prefix = S::name(S::slice($name, 0, -1));
                return array(
                    self::IMENIT => $prefix.'ь',
                    self::RODIT => $prefix.'и',
                    self::DAT => $prefix.'и',
                    self::VINIT => $prefix.'ь',
                    self::TVORIT => $prefix.'ью',
                    self::PREDLOJ => $prefix.'и',
                );
            } elseif (S::slice($name, -2) == 'нь') {
                // Рязань, Назрань
                $prefix = S::name(S::slice($name, 0, -1));
                return array(
                    self::IMENIT => $prefix.'ь',
                    self::RODIT => $prefix.'и',
                    self::DAT => $prefix.'и',
                    self::VINIT => $prefix.'ь',
                    self::TVORIT => $prefix.'ью',
                    self::PREDLOJ => $prefix.'и',
                );
            } else if (S::slice($name, -2) == 'ые') {
                // Набережные
                $prefix = S::name(S::slice($name, 0, -1));
                return array(
                    self::IMENIT => $prefix.'е',
                    self::RODIT => $prefix.'х',
                    self::DAT => $prefix.'м',
                    self::VINIT => $prefix.'е',
                    self::TVORIT => $prefix.'ми',
                    self::PREDLOJ => $prefix.'х',
                );
            } else if (S::slice($name, -2) == 'ны') {
                // Челны
                $prefix = S::name(S::slice($name, 0, -1));
                return array(
                    self::IMENIT => $prefix.'ы',
                    self::RODIT => $prefix.'ов',
                    self::DAT => $prefix.'ам',
                    self::VINIT => $prefix.'ы',
                    self::TVORIT => $prefix.'ами',
                    self::PREDLOJ => $prefix.'ах',
                );
            }

            $suffixes = array('ов', 'ёв', 'ев', 'ин', 'ын');
            if ((in_array(S::slice($name, -1), array('е', 'о')) && in_array(S::slice($name, -3, -1), $suffixes)) || in_array(S::slice($name, -2), $suffixes)) {
                // ово, ёво, ...
                if (in_array(S::slice($name, -3, -1), $suffixes)) {
                    $prefix = S::name(S::slice($name, 0, -1));
                }
                // ов, её, ...
                elseif (in_array(S::slice($name, -2), $suffixes)) {
                    $prefix = S::name($name);
                }
                return array(
                    self::IMENIT => S::name($name),
                    self::RODIT => $prefix.'а',
                    self::DAT => $prefix.'у',
                    self::VINIT => S::name($name),
                    self::TVORIT => $prefix.'ым',
                    self::PREDLOJ => $prefix.'е',
                );
            }
        }

        // if no rules matches or name is immutable
        $name = in_array($name, self::$abbreviations) ? S::upper($name) : S::name($name);
        return array_fill_keys(array(self::IMENIT, self::RODIT, self::DAT, self::VINIT, self::TVORIT, self::PREDLOJ), $name);
    }

    /**
     * Получение одной формы (падежа) названия.
     * @param string $name Название
     * @param integer $case Падеж. Одна из констант \morphos\Russian\Cases или \morphos\Cases.
     * @see \morphos\Russian\Cases
     * @return mixed
     */
    public static function getCase($name, $case)
    {
        $case = self::canonizeCase($case);
        $forms = self::getCases($name);
        return $forms[$case];
    }
}
