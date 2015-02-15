package com.piwik.intellijplugins.piwikstorm.services;

import com.intellij.psi.PsiElement;
import com.jetbrains.php.lang.psi.elements.PhpNamedElement;

/**
 * TODO
 */
public interface PiwikPsiElementMetadataProvider {

    /**
     * TODO
     */
    public String getPluginNamespaceOfElement(PsiElement element);

    /**
     * TODO
     */
    public String getPluginNameOfElement(PsiElement element);

    /**
     * TODO
     */
    public boolean isMarkedWithApi(PhpNamedElement element);
}
