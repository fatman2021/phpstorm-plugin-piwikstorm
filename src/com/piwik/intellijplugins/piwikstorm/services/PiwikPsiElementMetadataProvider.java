package com.piwik.intellijplugins.piwikstorm.services;

import com.intellij.psi.PsiElement;
import com.jetbrains.php.lang.psi.elements.PhpNamedElement;

/**
 * Application level service that can be used to get metadata information about Piwik
 * PHP types.
 *
 * Currently allows:
 *  - getting plugin namespace and plugin name of a PhpElement
 *  - determining whether a PhpNamedElement is exposed as Piwik @api
 */
public interface PiwikPsiElementMetadataProvider {

    /**
     * Returns the plugin base namespace for a PHP element or null if it does not
     * belong to a namespaced plugin.
     *
     * @param element The element to check.
     * @return the plugin namespace (eg <code>"\\Piwik\\Plugins\\MyPlugin"</code>) or null.
     */
    public String getPluginNamespaceOfElement(PsiElement element);

    /**
     * Returns the plugin name for a PHP element or null if it does not belong
     * to a namespaced plugin.
     *
     * @param element The element to check.
     * @return the plugin name (eg <code>"MyPlugin"</code>) or null.
     */
    public String getPluginNameOfElement(PsiElement element);

    /**
     * Returns true if an element is available as part of Piwik's documented @api.
     *
     * An element is part of Piwik's documented API if:
     *   - it is marked w/ the @api annotation
     *   - OR it is a class/interface and at least one method/field/const is marked with the @api annotation
     *   - OR it is a member and the class/interface it belongs to is marked with the @api annotation
     *
     * @param element The element to check.
     */
    public boolean isMarkedWithApi(PhpNamedElement element);
}
