# Changelog

<!-- There is always Unreleased section on the top. Subsections (Added, Changed, Fixed, Removed) should be added as needed. -->
## Unreleased
- Fix finding html tags when there are encoded html entities
- Add and throw `FindHtmlTagExcpetion` when there is a problem while finding a html tag

## 7.4.1 - 2023-11-29
- Fix finding a similar html tag

## 7.4.0 - 2023-10-25
- Encode html entities in `HtmlHelper` to prevent parse errors

## 7.3.0 - 2023-05-23
- Support `figure` html tag in `HtmlHelper::xpathHtmlDocument` method

## 7.2.0 - 2023-05-22
- Allow to parse multi-line html for
  - `HtmlHelper::findAllImages`
  - `HtmlHelper::findAllLinks`
- Add `HtmlHelper::xpathHtmlDocument` method
  - Require `ext-dom` extension

## 7.1.0 - 2022-10-10
- Add mime-type constants to `Image`

## 7.0.0 - 2022-07-11
- Allow Symfony 6
- Drop support for Symfony 4
- Require php 8.1
  - [BC] Use php 8.1 features and types

## 6.0.0 - 2022-07-11
- Make private library public
