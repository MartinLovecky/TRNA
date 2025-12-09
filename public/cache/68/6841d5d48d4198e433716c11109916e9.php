<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Markup;
use Twig\Source;
use Twig\Template;

/* maniaLinks/skip.xml.twig */
class __TwigTemplate_333adfcd2697e462f208c64e6b465956 extends Template
{
    private Source $source;
    /**
     * @var array<string, Template>
     */
    private array $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 1
        yield "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<manialink id=\"123\">
    <frame>
        <!-- Background -->
        <quad posn=\"-40 30 -32\" sizen=\"40 20\" style=\"Bgs1\" substyle=\"BgList\" />

        <!-- Header -->
        <label posn=\"-38 27 -31\" sizen=\"36 4\" halign=\"center\" valign=\"center\"
               textsize=\"3\" style=\"TextCardInfo\"
               text=\"";
        // line 10
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(((array_key_exists("header", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["header"]) || array_key_exists("header", $context) ? $context["header"] : (function () { throw new RuntimeError('Variable "header" does not exist.', 10, $this->source); })()), "Vote: Skip Track?")) : ("Vote: Skip Track?")), "html", null, true);
        yield "\" />
       <!-- Countdown -->
       <label id=\"Timer\" posn=\"-38 25 -31\" sizen=\"36 4\"
            halign=\"center\" valign=\"center\" textsize=\"3\"
            style=\"TextCardInfo\" text=\"";
        // line 14
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["remaining"]) || array_key_exists("remaining", $context) ? $context["remaining"] : (function () { throw new RuntimeError('Variable "remaining" does not exist.', 14, $this->source); })()), "html", null, true);
        yield "\" />
        <!-- Yes / No Buttons -->
        <quad posn=\"-40 10 -32\" sizen=\"19 3\" bgcolor=\"0F8\" />
        <quad posn=\"-19 10 -32\" sizen=\"19 3\" bgcolor=\"F00\" />

        <label posn=\"-36 8 -31\" sizen=\"14 2\" valign=\"center\" halign=\"center\"
               style=\"TextButtonNav\" text=\"YES (x";
        // line 20
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["yes"]) || array_key_exists("yes", $context) ? $context["yes"] : (function () { throw new RuntimeError('Variable "yes" does not exist.', 20, $this->source); })()), "html", null, true);
        yield ")\"
               action=\"";
        // line 21
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["actions"]) || array_key_exists("actions", $context) ? $context["actions"] : (function () { throw new RuntimeError('Variable "actions" does not exist.', 21, $this->source); })()), "yes", [], "any", false, false, false, 21), "html", null, true);
        yield "\" />

        <label posn=\"-14 8 -31\" sizen=\"14 2\" valign=\"center\" halign=\"center\"
               style=\"TextButtonNav\" text=\"NO (x";
        // line 24
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["no"]) || array_key_exists("no", $context) ? $context["no"] : (function () { throw new RuntimeError('Variable "no" does not exist.', 24, $this->source); })()), "html", null, true);
        yield ")\"
               action=\"";
        // line 25
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["actions"]) || array_key_exists("actions", $context) ? $context["actions"] : (function () { throw new RuntimeError('Variable "actions" does not exist.', 25, $this->source); })()), "no", [], "any", false, false, false, 25), "html", null, true);
        yield "\" />

        ";
        // line 27
        if ((($tmp = (isset($context["isAdmin"]) || array_key_exists("isAdmin", $context) ? $context["isAdmin"] : (function () { throw new RuntimeError('Variable "isAdmin" does not exist.', 27, $this->source); })())) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 28
            yield "        <!-- Admin-only options -->
        <quad posn=\"-40 5 -32\" sizen=\"19 3\" bgcolor=\"08F\" />
        <quad posn=\"-19 5 -32\" sizen=\"19 3\" bgcolor=\"888\" />

        <label posn=\"-36 3 -31\" sizen=\"14 2\" valign=\"center\" halign=\"center\"
               style=\"TextButtonNav\" text=\"PASS\"
               action=\"";
            // line 34
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["actions"]) || array_key_exists("actions", $context) ? $context["actions"] : (function () { throw new RuntimeError('Variable "actions" does not exist.', 34, $this->source); })()), "pass", [], "any", false, false, false, 34), "html", null, true);
            yield "\" />

        <label posn=\"-14 3 -31\" sizen=\"14 2\" valign=\"center\" halign=\"center\"
               style=\"TextButtonNav\" text=\"CANCEL\"
               action=\"";
            // line 38
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["actions"]) || array_key_exists("actions", $context) ? $context["actions"] : (function () { throw new RuntimeError('Variable "actions" does not exist.', 38, $this->source); })()), "cancel", [], "any", false, false, false, 38), "html", null, true);
            yield "\" />
        ";
        }
        // line 40
        yield "
        <!-- Close Button -->
        <label posn=\"-2 0 -31\" sizen=\"8 3\" valign=\"center\" halign=\"center\"
               style=\"TextButtonNav\" text=\"CLOSE\"
               action=\"";
        // line 44
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(((CoreExtension::getAttribute($this->env, $this->source, ($context["actions"] ?? null), "close", [], "any", true, true, false, 44)) ? (Twig\Extension\CoreExtension::default(CoreExtension::getAttribute($this->env, $this->source, (isset($context["actions"]) || array_key_exists("actions", $context) ? $context["actions"] : (function () { throw new RuntimeError('Variable "actions" does not exist.', 44, $this->source); })()), "close", [], "any", false, false, false, 44), 0)) : (0)), "html", null, true);
        yield "\" />
    </frame>
</manialink>
";
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "maniaLinks/skip.xml.twig";
    }

    /**
     * @codeCoverageIgnore
     */
    public function isTraitable(): bool
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo(): array
    {
        return  [116 => 44,  110 => 40,  105 => 38,  98 => 34,  90 => 28,  88 => 27,  83 => 25,  79 => 24,  73 => 21,  69 => 20,  60 => 14,  53 => 10,  42 => 1,];
    }

    public function getSourceContext(): Source
    {
        return new Source("<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<manialink id=\"123\">
    <frame>
        <!-- Background -->
        <quad posn=\"-40 30 -32\" sizen=\"40 20\" style=\"Bgs1\" substyle=\"BgList\" />

        <!-- Header -->
        <label posn=\"-38 27 -31\" sizen=\"36 4\" halign=\"center\" valign=\"center\"
               textsize=\"3\" style=\"TextCardInfo\"
               text=\"{{ header|default('Vote: Skip Track?')}}\" />
       <!-- Countdown -->
       <label id=\"Timer\" posn=\"-38 25 -31\" sizen=\"36 4\"
            halign=\"center\" valign=\"center\" textsize=\"3\"
            style=\"TextCardInfo\" text=\"{{ remaining }}\" />
        <!-- Yes / No Buttons -->
        <quad posn=\"-40 10 -32\" sizen=\"19 3\" bgcolor=\"0F8\" />
        <quad posn=\"-19 10 -32\" sizen=\"19 3\" bgcolor=\"F00\" />

        <label posn=\"-36 8 -31\" sizen=\"14 2\" valign=\"center\" halign=\"center\"
               style=\"TextButtonNav\" text=\"YES (x{{yes}})\"
               action=\"{{ actions.yes }}\" />

        <label posn=\"-14 8 -31\" sizen=\"14 2\" valign=\"center\" halign=\"center\"
               style=\"TextButtonNav\" text=\"NO (x{{no}})\"
               action=\"{{ actions.no }}\" />

        {% if isAdmin %}
        <!-- Admin-only options -->
        <quad posn=\"-40 5 -32\" sizen=\"19 3\" bgcolor=\"08F\" />
        <quad posn=\"-19 5 -32\" sizen=\"19 3\" bgcolor=\"888\" />

        <label posn=\"-36 3 -31\" sizen=\"14 2\" valign=\"center\" halign=\"center\"
               style=\"TextButtonNav\" text=\"PASS\"
               action=\"{{ actions.pass }}\" />

        <label posn=\"-14 3 -31\" sizen=\"14 2\" valign=\"center\" halign=\"center\"
               style=\"TextButtonNav\" text=\"CANCEL\"
               action=\"{{ actions.cancel }}\" />
        {% endif %}

        <!-- Close Button -->
        <label posn=\"-2 0 -31\" sizen=\"8 3\" valign=\"center\" halign=\"center\"
               style=\"TextButtonNav\" text=\"CLOSE\"
               action=\"{{ actions.close|default(0) }}\" />
    </frame>
</manialink>
", "maniaLinks/skip.xml.twig", "/home/yuha/Work/New folder/TRNA/public/templates/maniaLinks/skip.xml.twig");
    }
}
